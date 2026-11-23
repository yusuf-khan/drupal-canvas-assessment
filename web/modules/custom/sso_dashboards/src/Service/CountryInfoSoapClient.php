<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Retrieves country names and ISO codes from CountryInfoService.
 */
final class CountryInfoSoapClient {

  private const SOAP_NS = 'http://schemas.xmlsoap.org/soap/envelope/';
  private const REQUEST_NS = 'https://soap-service-free.mock.beeceptor.com/CountryInfoService';
  private const SOAP_ACTION = 'https://soap-service-free.mock.beeceptor.com/CountryInfoService.wso/ListOfCountryNamesByName';
  // Beeceptor's response uses the original CountryInfo namespace.
  private const RESPONSE_NS = 'http://www.oorsprong.org/websamples.countryinfo';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns countries as name and ISO code pairs.
   */
  public function getCountries(): array {
    $endpoint = $this->configFactory->get('sso_dashboards.settings')->get('soap_endpoint');
    if (!is_string($endpoint) || parse_url($endpoint, PHP_URL_SCHEME) !== 'https' || !parse_url($endpoint, PHP_URL_HOST)) {
      throw new UpstreamException('CountryInfo requires a valid HTTPS endpoint.');
    }
    $soapNamespace = self::SOAP_NS;
    $countryNamespace = self::REQUEST_NS;
    $envelope = <<<XML
      <soap:Envelope xmlns:soap="$soapNamespace" xmlns:country="$countryNamespace">
        <soap:Body><country:ListOfCountryNamesByName/></soap:Body>
      </soap:Envelope>
      XML;

    try {
      $response = $this->httpClient->request('POST', $endpoint, [
        'timeout' => 5,
        'allow_redirects' => FALSE,
        'connect_timeout' => 3,
        'headers' => ['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => '"' . self::SOAP_ACTION . '"'],
        'body' => $envelope,
      ]);
    }
    catch (GuzzleException $exception) {
      throw new UpstreamException('CountryInfo SOAP request failed.', 0, $exception);
    }

    $body = (string) $response->getBody();
    if ($response->getStatusCode() !== 200 || strlen($body) > 1_048_576) {
      throw new UpstreamException('CountryInfo returned an unexpected HTTP response.');
    }
    $document = new \DOMDocument();
    if (trim($body) === '' || !$document->loadXML($body, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING) || $document->doctype !== NULL) {
      throw new UpstreamException('CountryInfo returned invalid XML.');
    }

    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('soap', self::SOAP_NS);
    $xpath->registerNamespace('country', self::RESPONSE_NS);
    $result = $xpath->query('/soap:Envelope/soap:Body/country:ListOfCountryNamesByNameResponse/country:ListOfCountryNamesByNameResult')->item(0);
    if (!$result || $xpath->query('/soap:Envelope/soap:Body/soap:Fault')->length) {
      throw new UpstreamException('CountryInfo response did not contain a country list.');
    }

    $countries = [];
    foreach ($xpath->query('country:tCountryCodeAndName', $result) as $country) {
      $name = trim($xpath->evaluate('string(country:sName)', $country));
      $isoCode = trim($xpath->evaluate('string(country:sISOCode)', $country));
      if ($name === '' || !preg_match('/^[A-Z]{2}$/D', $isoCode)) {
        throw new UpstreamException('CountryInfo returned an incomplete country entry.');
      }
      $countries[] = ['name' => $name, 'isoCode' => $isoCode];
    }

    if ($countries === []) {
      throw new UpstreamException('CountryInfo returned an empty country list.');
    }

    return $countries;
  }

}
