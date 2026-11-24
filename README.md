# Drupal practical assessments

Both assessments are on branch `main` and can be reviewed on the root DDEV site. OpenAI Codex was the primary coding agent. See the [AI development log](web/themes/custom/agent-logs.md) for the implementation and review milestones.

| Assessment | Project directory | DDEV URL | Reviewer guide |
| --- | --- | --- | --- |
| Drupal CMS, SDC, and Canvas | Repository root | `https://drupal-canvas-assessment.ddev.site` | [Install Canvas](#setup-and-run) · [Validation](#canvas-validation-results) |
| Drupal 11 and Auth0 SSO | Repository root | `https://drupal-canvas-assessment.ddev.site/user/login` | [Configure Auth0 sign-in](#optional-auth0-sign-in-on-the-canvas-site) · [Validation](#auth0-validation-results) |

The [standalone Auth0 installation](auth0-assessment/README.md#setup-and-run) is also available in `auth0-assessment/` with its own DDEV configuration.

## Drupal CMS, SDC, and Canvas

The Specialty Food prototype uses Drupal CMS 2.2.10, Drupal core 11.4.13, Canvas 1.13.0, Canvas Full HTML 1.0.6, and Drush 13.8.1. The local environment used DDEV 1.25.5, PHP 8.4.27, and MariaDB 11.8. Composer restores the PHP dependencies from the lockfile.

### Setup and run

Install Git, Docker, and DDEV, then start Docker. Run this setup on a fresh DDEV database. The `site:install` command drops existing tables in the selected database.

```bash
git clone --branch main https://github.com/yusuf-khan/drupal-canvas-assessment.git
cd drupal-canvas-assessment
ddev start
ddev composer install
ddev drush site:install --existing-config --db-url=mysql://db:db@db/db --account-name=admin -y
ddev exec --dir /var/www/html/web php core/scripts/dr recipe /var/www/html/demo/sfa-homepage
ddev launch
```

The recipe imports the published Winter FancyFaire page and media. Open `/winter-fancyfaire` in a signed-out browser and check for the hero and four cards.

### Review the page in Canvas

1. Run `ddev drush user:login` and open the generated local sign-in link.
2. Go to **Content → Pages → Winter FancyFaire → Edit**.
3. In **Layers**, expand **Event hero → Desktop buttons** or **Card group → Cards** and select a child component to inspect its settings.

#### Set up a test editor

As the local administrator, create a new active user under **People → Add user** and assign **Content editor**. That role has access to create, edit, and publish Canvas pages and use the Canvas Full HTML text format. Sign in as this user in a private window and follow the steps below.

#### Compose the design in Canvas

1. Open **Content → Pages → Add Page** and set the title and an unused alias in **Page data**.
2. In **Library → Components → Specialty Food**, add **Event hero** and **Card group**. Add **Button link** instances to the hero’s **Desktop buttons** slot. Use **Mobile buttons** when the mobile labels or destinations differ.
3. Add four **Audience card** instances to the **Cards** slot. Configure the Maker, Buyer, Distributor, and Service Provider cards with photos and icons from the Media Library.
4. In **Review changes**, select the new page and publish. Open its URL in a signed-out window.

#### Optional Auth0 sign-in on the Canvas site

The Canvas configuration export includes the Auth0 dashboard module and its
Generic client in strict ID-token validation mode. On a separate Starter
installation, run `ddev drush en sso_dashboards -y` to enable this setup. Canvas
editor access continues to use the Drupal **Content editor** role.

For the bundled Auth0 application, add these URLs in Auth0:

- **Allowed Callback URLs** `https://drupal-canvas-assessment.ddev.site/openid-connect/auth0`
- **Allowed Logout URLs** `https://drupal-canvas-assessment.ddev.site/user/login`

The tracked `web/sites/default/settings.secret.php` loads local credentials.
For a fresh setup, add the Auth0 client secret to a one-line
`.auth0-client-secret` file in the Canvas project root. If you use local PHP
overrides, put them in `web/sites/default/settings.credentials.php`. The
loader uses that file when it exists. Git ignores both credential files.

Run `ddev drush cr`, then choose **Auth0** at `/user/login`. Configure a
different application at `/admin/config/people/openid-connect`. See the
[Auth0 role mapping guide](#auth0-and-test-users)
for the Post Login Action and test accounts.

### Component design

The [Specialty Food theme](web/themes/custom/sfa_theme/components) defines the reusable Single Directory Components. Canvas slots let editors compose the hero buttons and audience cards, and Canvas Full HTML and Media Library supply the text and image controls. See the [component specifications](web/themes/custom/component-specs.md) for props, defaults, and responsive behavior.

### Canvas validation results

| Check | Result |
| --- | --- |
| Fresh install | Installed from the tracked config on a fresh DDEV database, applied the demo recipe, and verified the published front page. |
| Editor workflow | A Content editor created a page, configured components and media, and published it. The draft was private and the published page opened anonymously. |
| Responsive layout | Checked 1920, 1366, 1024, 768, and 390px in Chromium without horizontal overflow. At 1920px, the hero measured 584px tall and the four cards measured 318px each with 24px gaps. |

[Published desktop page](screenshots/published-desktop.png) · [Canvas editor](screenshots/canvas-editor.png)

The reference pairs a 2028 title with a January 2027 date. Both values stay editable. Button and resource destinations use demo anchors. The theme serves Font Awesome Free 6.7.2 locally. Adobe Fonts requires network access.

## Auth0 SSO assessment

This Drupal 11 prototype signs users in with Auth0, reconciles their roles,
and serves role-protected dashboards. See the [reviewed module plan](module-plan.md).

### Auth0 architecture

```text
Drupal login page
  |
  v
Auth0 Universal Login
  |
  v
Auth0 Post Login Action adds roles to the ID token
  |
  v
Drupal verifies the token and creates or loads the local account
  |
  v
Drupal synchronizes roles and chooses the destination
  |
  +-- member ----> /dashboard/member ----> JSONPlaceholder REST
  |
  +-- exhibitor -> /dashboard/exhibitor -> Beeceptor SOAP
  |
  +-- no dashboard role -> Home with a status message
```

Drupal checks the required role on every dashboard request, including direct
visits.

### Auth0 environment and dependencies

Install Git, Docker, and [DDEV](https://ddev.com/get-started/).

| Component | Version |
| --- | --- |
| DDEV | 1.25.5 used for local testing |
| Drupal core | 11.4.13 tested. The custom module requires 11.1 or later in the 11.x series for attribute-based hooks. |
| PHP and database in DDEV | PHP 8.4 and MariaDB 11.8 |
| Drush | 13.8.1 |
| OpenID Connect | 3.x at commit `acc27bd` |
| External Auth | 2.0.15, installed through OpenID Connect |

Composer restores the exact dependency versions from `composer.lock`.


### Auth0 module evaluation

- **OpenID Connect** powers the current implementation. Its Generic client
  supports Auth0, account provisioning, login hooks, and provider logout.
  The pinned 3.x development revision supports strict ID-token validation.
  The custom `sso_dashboards` module adds role reconciliation, redirects,
  route access checks, and the REST and SOAP integrations.
- **Drupal OIDC Client** was evaluated for its signed-token validation and
  realm management. OpenID Connect’s authorization hooks and login destination
  API fit the dashboard flow.
- **miniOrange SAML SP** was evaluated on Drupal 11. Its licensed features for
  assertion-based role mapping and single logout made it a poor fit for this
  assessment.
  [miniOrange plan comparison](https://plugins.miniorange.com/drupal-saml-single-sign-on-sso#pricing)

### Auth0 and test users

1. Create an Auth0 **Regular Web Application** with a database connection,
   Authorization Code grant, and RS256 signing.
2. Add `https://drupal-assessment.ddev.site/openid-connect/auth0` to **Allowed
   Callback URLs** and `https://drupal-assessment.ddev.site/user/login` to
   **Allowed Logout URLs**. Use your site's origin if it differs. Enable
   RP-Initiated Logout discovery if the tenant omits `end_session_endpoint`.
3. Create two test users with distinct email addresses. Assign `member` to
   one and `exhibitor` to the other. Use emails that do not already belong to
   local Drupal accounts.
4. Create an Auth0 **Post Login** Action from
   [post-login-roles.js](auth0-assessment/auth0/post-login-roles.js). Replace the client ID
   placeholder, click **Deploy**, attach the Action under **Actions →
   Triggers → Post Login**, and click **Apply**.

The two-account live check used Auth0's native role assignments. Auth0 lists
Role Management as a paid feature. For a Free tenant, set administrator-managed
`app_metadata` to `{"roles":["member"]}` or `{"roles":["exhibitor"]}`
and change the Action's role source to
`const roles = event.user.app_metadata?.roles;`. Deploy the updated Action.
Both sources use the same Drupal mapping. The metadata path avoids reliance
on trial access to Role Management. It still needs a live replay on a Free
tenant. [Auth0 plan comparison](https://auth0.com/pricing)

The configuration export includes the Auth0 OpenID Connect client.
`auth0-assessment/web/sites/default/settings.secret.php` supplies the public Client ID for the
bundled Auth0 application. Create `auth0-assessment/.auth0-client-secret`
and put the current Auth0 Client Secret on a single line. Git ignores this
file. Run `ddev drush cr` after updating it.

For a different Auth0 application, update the public Client ID in
`auth0-assessment/web/sites/default/settings.secret.php`. For a different tenant, also update
the issuer and endpoints at `/admin/config/people/openid-connect`.

#### Reviewer test accounts

You can test Auth0 login with the two accounts I created below. Contact me
privately for their passwords and, for a fresh local install, the application
client secret for `.auth0-client-secret`.

| Auth0 username | Password | Role | Expected destination | Direct request returns HTTP 403 |
| --- | --- | --- | --- | --- |
| `khany762@gmail.com` | Shared privately | Member | `/dashboard/member` | `/dashboard/exhibitor` |
| `nkhanyusuf338@gmail.com` | Shared privately | Exhibitor | `/dashboard/exhibitor` | `/dashboard/member` |

Open `/user/login` in a private browser window and choose **Auth0**. Confirm
that sign-in opens the expected dashboard and that visiting the other
dashboard directly returns HTTP 403. Select **Log out** and confirm that
both dashboards return HTTP 403. Repeat with the other account.

### Role mapping and dashboards

External Auth links the Auth0 subject to its Drupal account. After each
successful login, the dashboard module reads the verified
`https://sso-prototype.example/roles` ID-token claim. It adds matching
dashboard roles and removes managed roles that are no longer assigned.
Unrelated Drupal roles are preserved.

| Drupal role | Destination | Live data |
| --- | --- | --- |
| `member` | `/dashboard/member` | JSONPlaceholder sample user's name, email, and company |
| `exhibitor` | `/dashboard/exhibitor` | Beeceptor SOAP country names and ISO codes |

With both roles, the default destination is the exhibitor dashboard.
Without a dashboard role, Drupal redirects home with a status message.
Configure role aliases, priority, or cache lifetime in
[`sso_dashboards.settings`](auth0-assessment/web/modules/custom/sso_dashboards/config/install/sso_dashboards.settings.yml).

#### External APIs

- The member dashboard sends a server-side `GET /users/1` to JSONPlaceholder.
  It displays the fictional user's name, email, and company. [REST endpoint](https://jsonplaceholder.typicode.com/users/1)
- The exhibitor dashboard sends a SOAP `POST` for `ListOfCountryNamesByName`
  to Beeceptor's `CountryInfoService.wso` endpoint and displays country names
  and ISO codes. [Beeceptor SOAP documentation](https://app.beeceptor.com/virtual-sandbox/soap-service-free)

Both public HTTPS services require no API key. Drupal caches validated results
for 60 seconds and renders the signed-in user's identity on each response.

### Auth0 validation results

The DDEV suite passed **12 tests with 86 assertions**. The browser test
installs a fresh Drupal Standard site and enables the assessment module.
The tests cover account provisioning, role reconciliation, redirects,
access checks, caching, and API failure handling with fixtures. PHPUnit also
reported four upstream deprecations from External Auth and its Views
configuration. Some of the deprecated APIs are scheduled for removal in
Drupal 12. Recheck External Auth compatibility before a core upgrade.

```sh
ddev exec env SIMPLETEST_BASE_URL=http://localhost \
  SIMPLETEST_DB=mysql://db:db@db/db BROWSERTEST_OUTPUT_DIRECTORY=/tmp \
  vendor/bin/phpunit -c web/core web/modules/custom/sso_dashboards/tests/src
```

| Check | Result |
| --- | --- |
| Fresh installation | Drupal installed from `config/sync`; a follow-up import reported no changes |
| Live Auth0 member | Assigned `member`, redirected to the member dashboard, HTTP 200 for member and 403 for exhibitor |
| Live Auth0 exhibitor | Assigned `exhibitor`, redirected to the exhibitor dashboard, HTTP 200 for exhibitor and 403 for member |
| Live Auth0 logout | Both accounts returned to Drupal after Auth0 logout. Both dashboards returned 403 in each logged-out session. |
| Live APIs | REST returned Leanne Graham’s sample record. SOAP returned 246 countries. |
| Direct URL access | Browser tests verified 403 for wrong-role and anonymous requests. |

### Auth0 challenges and blockers

#### Resolved during development

- **Missing roles after sign-in.** The deployed Auth0 Action had to be attached
  to the Post Login flow. Applying the binding restored the role claim.
- **Fresh install failed with duplicate configuration.** OpenID Connect owns
  `openid_connect.settings`. The custom module applies its defaults in an
  install hook, and the fresh install from `config/sync` passed.
- **Missing External Auth table.** Stale local module registrations left the
  `authmap` table missing. The local database was repaired, and fresh installs
  create the table automatically.


## Submission

Share the [repository](https://github.com/yusuf-khan/drupal-canvas-assessment), branch `main`, and the output of `git rev-parse HEAD` for the final reviewed commit. Share any reviewer credentials privately.
