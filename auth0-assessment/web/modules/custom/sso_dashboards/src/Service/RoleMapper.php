<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards\Service;

use Drupal\user\UserInterface;

/**
 * Synchronizes the Drupal roles managed by Auth0.
 */
final class RoleMapper {

  public const ROLES = ['exhibitor', 'member'];

  public const DEFAULT_MAPPING = [
    'member' => ['member'],
    'exhibitor' => ['exhibitor'],
  ];

  /**
   * Reconciles the managed roles and saves changes to the account.
   */
  public function sync(UserInterface $account, array $claimRoles, array $mapping = self::DEFAULT_MAPPING): void {
    $claimRoles = array_filter($claimRoles, 'is_string');
    $changed = FALSE;
    foreach (self::ROLES as $role) {
      $assigned = $account->hasRole($role);
      $required = array_intersect($mapping[$role] ?? [], $claimRoles) !== [];
      if ($assigned === $required) {
        continue;
      }
      if ($required) {
        $account->addRole($role);
      }
      else {
        $account->removeRole($role);
      }
      $changed = TRUE;
    }
    if ($changed) {
      $account->save();
    }
  }

}
