<?php

declare(strict_types=1);

namespace Drupal\Tests\sso_dashboards\Unit;

use Drupal\sso_dashboards\Service\RoleMapper;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;

#[Group('sso_dashboards')]
final class RoleMapperTest extends UnitTestCase {

  public function testManagedRolesAreReconciledOnce(): void {
    $roles = ['member', 'administrator'];
    $account = $this->createMock(UserInterface::class);
    $account->method('hasRole')->willReturnCallback(static function (string $role) use (&$roles): bool {
      return in_array($role, $roles, TRUE);
    });
    $account->expects($this->once())->method('addRole')->with('exhibitor')->willReturnCallback(static function (string $role) use (&$roles, $account): UserInterface {
      $roles[] = $role;
      return $account;
    });
    $account->expects($this->once())->method('removeRole')->with('member')->willReturnCallback(static function (string $role) use (&$roles, $account): UserInterface {
      $roles = array_values(array_diff($roles, [$role]));
      return $account;
    });
    $account->expects($this->once())->method('save');

    $mapper = new RoleMapper();
    $mapping = [
      'member' => ['Customer'],
      'exhibitor' => ['Trade Partner', 'exhibitor'],
      'administrator' => ['Trade Partner'],
    ];
    $mapper->sync($account, ['Trade Partner', 'unknown', ['Customer']], $mapping);
    $mapper->sync($account, ['Trade Partner', 'unknown'], $mapping);
    $this->assertSame(['administrator', 'exhibitor'], $roles);
  }

}
