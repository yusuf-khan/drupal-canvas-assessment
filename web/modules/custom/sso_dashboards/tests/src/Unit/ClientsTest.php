<?php

declare(strict_types=1);

namespace Drupal\Tests\sso_dashboards\Unit;

use Drupal\sso_dashboards\Service\CountryInfoSoapClient;
use Drupal\sso_dashboards\Service\JsonPlaceholderClient;
use Drupal\sso_dashboards\Service\UpstreamException;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('sso_dashboards')]
final class ClientsTest extends UnitTestCase {

  public function testRestUser(): void {
    $service = $this->restClient(file_get_contents(__DIR__ . '/../../fixtures/user.json'));
    $this->assertSame([
      'name' => '<em>Sample Member</em>',
      'email' => 'member@example.test',
      'company' => ['name' => 'Sample Company'],
    ], $service->getUser());
  }

  public function testSoapCountries(): void {
    $service = $this->soapClient(file_get_contents(__DIR__ . '/../../fixtures/countries.xml'));
    $this->assertSame([['name' => 'Canada', 'isoCode' => 'CA']], $service->getCountries());
  }

  #[DataProvider('invalidPayloads')]
  public function testInvalidPayload(string $protocol, string $body): void {
    $this->expectException(UpstreamException::class);
    if ($protocol === 'REST') {
      $this->restClient($body)->getUser();
    }
    else {
      $this->soapClient($body)->getCountries();
    }
  }

  public static function invalidPayloads(): array {
    return [
      'invalid JSON' => ['REST', '{'],
      'missing JSON fields' => ['REST', '{"name":"Sample","email":"sample@example.test","company":{}}'],
      'invalid XML' => ['SOAP', '<'],
      'missing SOAP result' => ['SOAP', '<response/>'],
      'unexpected SOAP entry' => ['SOAP', str_replace('<sISOCode>CA</sISOCode>', '', file_get_contents(__DIR__ . '/../../fixtures/countries.xml'))],
      'DTD disallowed' => ['SOAP', str_replace('?>', '?>' . "\n<!DOCTYPE Envelope>", file_get_contents(__DIR__ . '/../../fixtures/countries.xml'))],
    ];
  }

  private function restClient(string $body): JsonPlaceholderClient {
    return new JsonPlaceholderClient($this->httpClient($body), $this->getConfigFactoryStub([
      'sso_dashboards.settings' => ['rest_base_url' => 'https://rest.example.test'],
    ]));
  }

  private function soapClient(string $body): CountryInfoSoapClient {
    return new CountryInfoSoapClient($this->httpClient($body), $this->getConfigFactoryStub([
      'sso_dashboards.settings' => ['soap_endpoint' => 'https://soap.example.test'],
    ]));
  }

  private function httpClient(string $body): Client {
    return new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], $body)]))]);
  }

}
