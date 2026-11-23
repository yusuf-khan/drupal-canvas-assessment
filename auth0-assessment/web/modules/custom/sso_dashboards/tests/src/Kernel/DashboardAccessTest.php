<?php

declare(strict_types=1);

namespace Drupal\Tests\sso_dashboards\Kernel;

use Drupal\Core\Session\UserSession;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('sso_dashboards')]
#[RunTestsInSeparateProcesses]
final class DashboardAccessTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'field', 'file', 'externalauth', 'openid_connect', 'sso_dashboards'];

  public function testRouteAccess(): void {
    $this->installConfig(['sso_dashboards']);
    $this->container->get('router.builder')->rebuild();
    $access = $this->container->get('access_manager');

    foreach ([
      [0, ['anonymous'], FALSE, FALSE],
      [2, ['authenticated'], FALSE, FALSE],
      [3, ['member'], TRUE, FALSE],
      [4, ['exhibitor'], FALSE, TRUE],
      [5, ['member', 'exhibitor'], TRUE, TRUE],
      [1, ['administrator'], FALSE, FALSE],
    ] as [$uid, $roles, $member, $exhibitor]) {
      $account = new UserSession(['uid' => $uid, 'roles' => $roles]);
      $this->assertSame($member, $access->checkNamedRoute('sso_dashboards.member', [], $account));
      $this->assertSame($exhibitor, $access->checkNamedRoute('sso_dashboards.exhibitor', [], $account));
    }
  }

}
