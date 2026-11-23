<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Retrieves the member dashboard's sample user.
 */
final class JsonPlaceholderClient {

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the sample user with name, email, and company name.
   */
  public function getUser(int $id = 1): array {
    $base = $this->configFactory->get('sso_dashboards.settings')->get('rest_base_url');
    if (!is_string($base) || parse_url($base, PHP_URL_SCHEME) !== 'https' || !parse_url($base, PHP_URL_HOST)) {
      throw new UpstreamException('JSONPlaceholder requires a valid HTTPS endpoint.');
    }
    try {
      $response = $this->httpClient->request('GET', rtrim($base, '/') . '/users/' . $id, [
        'timeout' => 5,
        'allow_redirects' => FALSE,
        'connect_timeout' => 3,
        'headers' => ['Accept' => 'application/json'],
      ]);
      $body = (string) $response->getBody();
      if ($response->getStatusCode() !== 200 || strlen($body) > 1_048_576) {
        throw new UpstreamException('JSONPlaceholder returned an unexpected HTTP response.');
      }
      $data = json_decode($body, TRUE, 32, JSON_THROW_ON_ERROR);
    }
    catch (GuzzleException | \JsonException $exception) {
      throw new UpstreamException('JSONPlaceholder request or decoding failed.', 0, $exception);
    }

    $name = $data['name'] ?? NULL;
    $email = $data['email'] ?? NULL;
    $company = $data['company']['name'] ?? NULL;
    foreach ([$name, $email, $company] as $value) {
      if (!is_string($value) || trim($value) === '') {
        throw new UpstreamException('JSONPlaceholder response is missing required user fields.');
      }
    }

    return ['name' => $name, 'email' => $email, 'company' => ['name' => $company]];
  }

}
