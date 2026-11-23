<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Replaces the dashboard clients' HTTP transport for browser tests.
 */
final class SsoDashboardsTestServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container): void {
    $container->getDefinition('http_client')->setFactory([self::class, 'createHttpClient']);
  }

  public static function createHttpClient(): Client {
    $handler = static function (RequestInterface $request): Response {
      $requestPath = $request->getMethod() . ' ' . $request->getUri()->getPath();
      if ($requestPath === 'GET /users/1') {
        $state = \Drupal::state();
        $state->set('sso_dashboards_test.rest_requests', $state->get('sso_dashboards_test.rest_requests', 0) + 1);
      }
      $fixtures = dirname(__DIR__, 3) . '/fixtures';
      return match ($requestPath) {
        'GET /users/1' => new Response(200, [], file_get_contents($fixtures . '/user.json')),
        'POST /CountryInfoService.wso' => $request->getHeaderLine('SOAPAction') === '"https://soap-service-free.mock.beeceptor.com/CountryInfoService.wso/ListOfCountryNamesByName"'
          && str_contains((string) $request->getBody(), 'xmlns:country="https://soap-service-free.mock.beeceptor.com/CountryInfoService"')
          && str_contains((string) $request->getBody(), '<country:ListOfCountryNamesByName/>')
          ? new Response(200, [], file_get_contents($fixtures . '/countries.xml'))
          : new Response(400),
        default => new Response(503),
      };
    };
    return new Client(['handler' => HandlerStack::create(new MockHandler(array_fill(0, 10, $handler)))]);
  }

}
