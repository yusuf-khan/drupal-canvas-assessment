<?php

declare(strict_types=1);

namespace Drupal\Tests\sso_dashboards\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\openid_connect\Entity\OpenIDConnectClientEntity;
use Drupal\openid_connect\OpenIDConnectIdTokenValidatorInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('sso_dashboards')]
#[RunTestsInSeparateProcesses]
final class LoginFlowTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'field', 'file', 'externalauth', 'openid_connect', 'sso_dashboards'];

  public function testFirstLoginAndReturningAccount(): void {
    $this->installEntitySchema('user');
    $this->installSchema('externalauth', ['authmap']);
    $this->installConfig(['system', 'user', 'openid_connect', 'sso_dashboards']);
    $this->container->get('router.builder')->rebuild();
    $this->config('user.settings')->set('register', UserInterface::REGISTER_ADMINISTRATORS_ONLY)->save();
    $this->config('openid_connect.settings')->set('override_registration_settings', TRUE)->save();

    User::create(['name' => 'local_admin', 'mail' => 'local@example.test', 'status' => 1])->save();
    $client = OpenIDConnectClientEntity::create([
      'id' => 'auth0',
      'label' => 'Auth0',
      'plugin' => 'generic',
      'settings' => [
        'client_id' => 'test-client',
        'authorization_endpoint' => 'https://auth0.example.test/authorize',
        'token_endpoint' => 'https://auth0.example.test/oauth/token',
        'userinfo_endpoint' => '',
        'verify_id_token' => 'strict',
        'issuer' => 'https://auth0.example.test/',
        'jwks_uri' => 'https://auth0.example.test/.well-known/jwks.json',
      ],
    ]);

    $this->config('sso_dashboards.settings')
      ->set('role_mapping', [
        'member' => ['Customer'],
        'exhibitor' => ['Trade Partner'],
        'reviewer' => ['Reviewer'],
      ])
      ->save();
    $claims = [
      'sub' => 'auth0|sample',
      'email' => 'sample@example.test',
      'email_verified' => TRUE,
      'https://sso-prototype.example/roles' => ['Customer', 'Trade Partner', 'administrator'],
    ];
    $validator = $this->createMock(OpenIDConnectIdTokenValidatorInterface::class);
    $validator->method('isAvailable')->willReturn(TRUE);
    $validator->method('validate')->willReturnCallback(static function () use (&$claims): array {
      return $claims;
    });
    $this->container->set('openid_connect.id_token_validator', $validator);
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $request->attributes->set('_route', 'openid_connect.redirect_controller_redirect');

    $oidc = $this->container->get('openid_connect.openid_connect');
    $session = $this->container->get('openid_connect.session');
    $currentUser = $this->container->get('current_user');
    $tokens = ['id_token' => 'fixture-token', 'access_token' => 'fixture-access-token'];
    $this->assertFalse($oidc->completeAuthorization($client, []));
    $this->assertTrue($currentUser->isAnonymous());

    $this->assertTrue($oidc->completeAuthorization($client, $tokens));
    $uid = $currentUser->id();
    $this->assertNotEquals(1, $uid);
    $this->assertSame('sample@example.test', $currentUser->getEmail());
    $this->assertTrue($currentUser->hasRole('member'));
    $this->assertTrue($currentUser->hasRole('exhibitor'));
    $this->assertSame('/dashboard/exhibitor', $session->getRedirectLocation()->toString());

    $currentUser->setAccount(new AnonymousUserSession());
    $this->config('sso_dashboards.settings')->set('redirect_priority', ['member', 'exhibitor'])->save();
    $this->assertTrue($oidc->completeAuthorization($client, $tokens));
    $this->assertSame($uid, $currentUser->id());
    $this->assertSame('/dashboard/member', $session->getRedirectLocation()->toString());

    $currentUser->setAccount(new AnonymousUserSession());
    unset($claims['https://sso-prototype.example/roles']);
    $this->assertTrue($oidc->completeAuthorization($client, $tokens));
    $this->assertSame($uid, $currentUser->id());
    $this->assertSame(['authenticated'], $currentUser->getRoles());
    $this->assertSame('/', $session->getRedirectLocation()->toString());
  }

}
