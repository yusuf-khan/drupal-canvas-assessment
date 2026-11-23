<?php

declare(strict_types=1);

namespace Drupal\sso_dashboards\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\sso_dashboards\Service\RoleMapper;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Synchronizes Auth0 roles and selects dashboard destinations after login.
 */
final class LoginHooks {

  private const LOGIN_REQUEST = '_sso_dashboards_auth0_login';

  public function __construct(
    private readonly RoleMapper $mapper,
    private readonly OpenIDConnectSessionInterface $session,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly RequestStack $requestStack,
    private readonly AccountProxyInterface $currentUser,
    private readonly MessengerInterface $messenger,
    private readonly TranslationInterface $translation,
  ) {}

  /**
   * Requires verified Auth0 claims and recognizes a login callback.
   */
  #[Hook('openid_connect_pre_authorize')]
  public function preAuthorize(UserInterface|bool $account, array $context): ?bool {
    $client = $this->configFactory->get('sso_dashboards.settings')->get('client_id');
    if ($context['plugin_id'] !== $client) {
      return NULL;
    }
    if (($context['id_token_verified'] ?? FALSE) !== TRUE) {
      return FALSE;
    }

    // The hook also fires when an authenticated user links an account.
    $request = $this->requestStack->getCurrentRequest();
    if ($this->currentUser->isAnonymous() && $request?->attributes->get('_route') === 'openid_connect.redirect_controller_redirect') {
      $request->attributes->set(self::LOGIN_REQUEST, $client);
    }
    return NULL;
  }

  /**
   * Synchronizes roles and sets the contributed client's login destination.
   */
  #[Hook('openid_connect_post_authorize')]
  public function postAuthorize(UserInterface $account, array $context): void {
    $request = $this->requestStack->getCurrentRequest();
    if (($context['id_token_verified'] ?? FALSE) !== TRUE || $request?->attributes->get(self::LOGIN_REQUEST) !== $context['plugin_id']) {
      return;
    }
    $request->attributes->remove(self::LOGIN_REQUEST);

    $settings = $this->configFactory->get('sso_dashboards.settings');
    $claim = $context['user_data'][$settings->get('role_claim')] ?? NULL;
    $this->mapper->sync(
      $account,
      is_array($claim) && array_is_list($claim) ? $claim : [],
      $settings->get('role_mapping') ?? RoleMapper::DEFAULT_MAPPING,
    );
    if ($role = $this->preferredRole($account)) {
      $this->session->saveTargetLinkUri('/dashboard/' . $role);
      return;
    }

    $this->messenger->addStatus($this->translation->translate('You are signed in. No dashboard is assigned to your account.'));
    $this->session->saveTargetLinkUri('/<front>');
  }

  /**
   * Adds a submit handler after Drupal authenticates the local user.
   */
  #[Hook('form_user_login_form_alter')]
  public function alterLocalLogin(array &$form): void {
    $form['#submit'][] = self::class . ':redirectLocalLogin';
  }

  /**
   * Replaces the default profile redirect for a local dashboard user.
   */
  public function redirectLocalLogin(array &$form, FormStateInterface $formState): void {
    if (!$this->currentUser->isAuthenticated() || $this->requestStack->getCurrentRequest()?->query->has('destination')) {
      return;
    }

    if ($role = $this->preferredRole($this->currentUser)) {
      $formState->setRedirect('sso_dashboards.' . $role);
    }
  }

  /**
   * Returns the account's highest-priority dashboard role.
   */
  private function preferredRole(AccountInterface $account): ?string {
    $priority = $this->configFactory->get('sso_dashboards.settings')->get('redirect_priority');
    foreach (array_merge($priority, RoleMapper::ROLES) as $role) {
      if (in_array($role, RoleMapper::ROLES, TRUE) && $account->hasRole($role)) {
        return $role;
      }
    }
    return NULL;
  }

}
