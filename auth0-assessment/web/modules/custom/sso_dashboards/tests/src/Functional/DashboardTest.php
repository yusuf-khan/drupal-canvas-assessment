<?php

declare(strict_types=1);

namespace Drupal\Tests\sso_dashboards\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('sso_dashboards')]
#[RunTestsInSeparateProcesses]
final class DashboardTest extends BrowserTestBase {

  protected static $modules = ['sso_dashboards', 'sso_dashboards_test'];
  protected $profile = 'standard';
  protected $defaultTheme = 'stark';

  public function testDashboards(): void {
    $this->assertSame('below', $this->config('openid_connect.settings')->get('user_login_display'));
    $this->drupalGet('dashboard/member');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('dashboard/exhibitor');
    $this->assertSession()->statusCodeEquals(403);

    $member = $this->drupalCreateUser();
    $member->addRole('member')->save();
    $this->useOneTimeLoginLinks = FALSE;
    $this->drupalLogin($member);
    $this->assertSession()->addressEquals('dashboard/member?check_logged_in=1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($member->getAccountName());
    $this->assertSession()->elementTextContains('css', 'dd', '<em>Sample Member</em>');
    $this->assertSession()->pageTextContains('member@example.test');
    $this->assertSession()->pageTextContains('Sample Company');
    $this->drupalGet('dashboard/member');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame(1, $this->container->get('keyvalue')->get('state')->get('sso_dashboards_test.rest_requests'));

    $secondMember = $this->drupalCreateUser();
    $secondMember->addRole('member')->save();
    $this->drupalLogin($secondMember);
    $this->assertSession()->pageTextContains($secondMember->getAccountName());
    $this->assertSession()->pageTextNotContains($member->getAccountName());
    $this->assertSession()->pageTextContains('Sample Company');
    $this->assertSame(1, $this->container->get('keyvalue')->get('state')->get('sso_dashboards_test.rest_requests'));
    $this->drupalGet('dashboard/exhibitor');
    $this->assertSession()->statusCodeEquals(403);

    $this->config('sso_dashboards.settings')->set('rest_base_url', 'https://rest.example.test/unavailable')->save();
    $this->drupalGet('dashboard/member');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The service is currently unavailable.');
    $this->drupalLogout();

    $exhibitor = $this->drupalCreateUser();
    $exhibitor->addRole('exhibitor')->save();
    $this->drupalLogin($exhibitor);
    $this->assertSession()->addressEquals('dashboard/exhibitor?check_logged_in=1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($exhibitor->getAccountName());
    $this->assertSession()->elementTextContains('css', 'td', 'Canada');
    $this->assertSession()->pageTextContains('CA');
    $this->drupalGet('dashboard/member');
    $this->assertSession()->statusCodeEquals(403);

    $localUser = $this->drupalCreateUser();
    $this->drupalLogin($localUser);
    $this->assertSession()->addressEquals('user/' . $localUser->id() . '?check_logged_in=1');
  }

}
