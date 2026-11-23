<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Shares validated public API results between dashboard users.
 */
final class DashboardData {

  public function __construct(
    private readonly JsonPlaceholderClient $restClient,
    private readonly CountryInfoSoapClient $soapClient,
    private readonly CacheBackendInterface $cache,
    private readonly TimeInterface $time,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the public member sample record.
   */
  public function getUser(): array {
    return $this->remember('rest_base_url', $this->restClient->getUser(...));
  }

  /**
   * Returns the public country list.
   */
  public function getCountries(): array {
    return $this->remember('soap_endpoint', $this->soapClient->getCountries(...));
  }

  /**
   * Caches only successfully validated data until its configured expiration.
   */
  private function remember(string $endpointKey, callable $fetch): array {
    $settings = $this->configFactory->get('sso_dashboards.settings');
    $ttl = (int) $settings->get('cache_max_age');
    if ($ttl <= 0) {
      return $fetch();
    }

    $cid = 'sso_dashboards:' . $endpointKey . ':' . hash('sha256', $ttl . ':' . $settings->get($endpointKey));
    if ($item = $this->cache->get($cid)) {
      return $item->data;
    }

    $data = $fetch();
    $this->cache->set($cid, $data, $this->time->getCurrentTime() + $ttl, ['config:sso_dashboards.settings']);
    return $data;
  }

}
