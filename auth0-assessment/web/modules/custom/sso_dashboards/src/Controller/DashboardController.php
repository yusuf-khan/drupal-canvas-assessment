<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sso_dashboards\Service\DashboardData;
use Drupal\sso_dashboards\Service\UpstreamException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the member and exhibitor dashboards.
 *
 * Access is enforced by the routes (_role requirement), not here.
 */
final class DashboardController extends ControllerBase {

  public function __construct(
    private readonly DashboardData $dashboardData,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('sso_dashboards.data'),
    );
  }

  /**
   * Fetches and renders the sample user for members.
   */
  public function member(): array {
    $build = $this->buildBase($this->t('Member'));

    try {
      $user = $this->dashboardData->getUser();
    }
    catch (UpstreamException $exception) {
      return $this->buildFailure($build, $exception);
    }

    $build['data'] = [
      '#type' => 'inline_template',
      '#template' => '<dl><dt>{{ name_label }}</dt><dd>{{ name }}</dd><dt>{{ email_label }}</dt><dd>{{ email }}</dd><dt>{{ company_label }}</dt><dd>{{ company }}</dd></dl>',
      '#context' => [
        'name_label' => $this->t('Name'),
        'name' => (string) ($user['name'] ?? ''),
        'email_label' => $this->t('Email'),
        'email' => (string) ($user['email'] ?? ''),
        'company_label' => $this->t('Company'),
        'company' => (string) ($user['company']['name'] ?? ''),
      ],
    ];

    return $build;
  }

  /**
   * Fetches and renders the country list for exhibitors.
   */
  public function exhibitor(): array {
    $build = $this->buildBase($this->t('Exhibitor'));

    try {
      $countries = $this->dashboardData->getCountries();
    }
    catch (UpstreamException $exception) {
      return $this->buildFailure($build, $exception);
    }

    $rows = [];
    foreach ($countries as $country) {
      if (!isset($country['name'], $country['isoCode'])) {
        continue;
      }
      $rows[] = [
        ['data' => ['#plain_text' => (string) $country['name']]],
        ['data' => ['#plain_text' => (string) $country['isoCode']]],
      ];
    }

    $build['data'] = [
      '#type' => 'table',
      '#header' => [$this->t('Country'), $this->t('ISO code')],
      '#rows' => $rows,
      '#empty' => $this->t('No countries were returned.'),
    ];

    return $build;
  }

  /**
   * Builds the identity line shared by both dashboards.
   */
  private function buildBase(string|\Stringable $role): array {
    return [
      // DashboardData caches public API results independently. Render the
      // signed-in identity for the current account on each request.
      '#cache' => ['contexts' => ['user'], 'max-age' => 0],
      'identity' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Signed in as @name (@role)', [
          '@name' => $this->currentUser()->getDisplayName(),
          '@role' => $role,
        ]),
      ],
    ];
  }

  /**
   * Logs an upstream failure and returns a safe, uncacheable message.
   */
  private function buildFailure(array $build, UpstreamException $exception): array {
    $this->getLogger('sso_dashboards')->warning('Upstream request failed: @message', [
      '@message' => $exception->getMessage(),
    ]);

    $build['data'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The service is currently unavailable. Please try again later.'),
      '#attributes' => ['role' => 'status'],
    ];

    return $build;
  }

}
