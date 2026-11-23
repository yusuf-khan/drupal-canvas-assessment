# Drupal practical assessments

This repository contains two independently runnable Drupal prototypes. OpenAI
Codex was the primary coding agent. The projects share this guide and the
[AI development log](agent-logs.md).

| Assessment | DDEV project directory | Local URL | Setup and validation |
| --- | --- | --- | --- |
| Drupal CMS, SDC, and Canvas | Repository root | `https://drupal-canvas-assessment.ddev.site` | [Canvas setup](#install-from-scratch) · [Canvas validation](#canvas-validation-results) |
| Drupal 11 and Auth0 SSO | `auth0-assessment/` | `https://drupal-assessment.ddev.site` | [Auth0 setup](#install-the-auth0-assessment-from-config) · [Auth0 validation](#auth0-validation-results) |

## Get the combined repository

Install Git, Docker, and [DDEV](https://ddev.com/get-started/). Start Docker and
clone the repository once.

```bash
git clone --branch main https://github.com/yusuf-khan/drupal-canvas-assessment.git
cd drupal-canvas-assessment
```

Run DDEV commands from the assessment’s project directory. Both databases can
run in parallel. Use the install commands below on empty databases.

## Drupal CMS, SDC, and Canvas

The Specialty Food prototype uses reusable Single Directory Components and
Canvas slots for editor-managed content, layout, and publication. See the
[component specifications](component-specs.md).

### Environment and dependencies

| Dependency | Version used |
| --- | --- |
| Drupal CMS | 2.2.10 |
| Drupal core | 11.4.13 |
| Canvas Full HTML | 1.0.6 |
| Drupal Canvas | 1.13.0 |
| PHP | 8.4.27 |
| MariaDB | 11.8 |
| Drush | 13.8.1 |
| DDEV | 1.25.5 |
| Font Awesome Free | 6.7.2 |

The DDEV configuration serves Drupal from `web/`. Composer locks the project dependencies.

### Install from scratch

Run these commands from the repository root.

```bash
ddev start
ddev composer install
```

Before running `site:install`, edit the root site's ignored
`web/sites/default/settings.php`. Replace any commented example or existing
`config_sync_directory` setting with the following line inside the PHP block.
If `settings.php` is missing, copy `web/sites/default/default.settings.php`
to `web/sites/default/settings.php` first.

```php
$settings['config_sync_directory'] = dirname($app_root) . '/config/sync';
```

Check the path before installing:

```bash
ddev drush status --fields=config-sync
test -f config/sync/core.extension.yml
```

The config-sync value should be `/var/www/html/config/sync`. If it still shows
`sites/default/files/sync`, Drush is using DDEV's fallback. Check that you
edited the root site's `settings.php` and that your assignment is active PHP.

On a fresh DDEV database, install Drupal from the
[Canvas configuration export](config/sync), then apply the demo recipe to
import the published page and its media. `site:install` drops existing
tables in the selected database.

```bash
ddev drush site:install --existing-config --db-url=mysql://db:db@db/db --account-name=admin -y
ddev exec --dir /var/www/html/web php core/scripts/dr recipe /var/www/html/demo/sfa-homepage
ddev launch
```

Drush prints the local administrator credentials. The configuration export
includes the theme and modules, and the recipe provides the Winter FancyFaire
page and media. If you already installed a site with the Drupal CMS **Starter**
template, enable `sfa_theme`, make it the default theme, rebuild the cache,
and apply the demo recipe. Use `ddev drush config:import` only on a site with
the same UUID as the export.

1. Open the front page in a signed-out window. Check the event hero and four
   cards. The demo is also available at `/winter-fancyfaire`.
2. Run `ddev drush user:login` and open the generated sign-in link.
3. Go to **Content → Pages → Winter FancyFaire → Edit**. Expand **Event
   hero → Desktop buttons** or **Card group → Cards** in **Layers**.

To verify non-admin authoring, [set up a test editor](#set-up-a-test-editor)
and [compose a page from scratch](#compose-the-design-in-canvas).

### Update an existing Canvas demo

For an existing installation that already uses the `sfa_theme` components, rebuild caches and run the migration to update its saved layouts and drafts.

```bash
ddev drush cache:rebuild
ddev exec --dir /var/www/html/web /var/www/html/vendor/bin/drush php:script /var/www/html/scripts/migrate-canvas-layout-fields.php
ddev drush cache:rebuild
```

The script converts older headings and descriptions to rich text, moves earlier hero buttons into an empty Desktop buttons slot, and removes the retired heading level setting.

### Canvas module evaluation

| Module or feature | Decision |
| --- | --- |
| Drupal Canvas | Owns page composition, autosaves, previews, and publishing. Its component library lets editors assemble the layout from a blank page. |
| Core Single Directory Components | Defines reusable typography and button atoms, the Event hero, Container, Card group, and Audience card. Canvas slots let editors compose and reorder them. |
| Canvas Full HTML | Provides the CKEditor 5 text format used by rich-text headings and descriptions. |
| Core Media and Media Library | Provide the image fields for selecting reusable media and managing photo alternative text. |
| Custom `sfa_theme` theme | Packages the Atomic Design components, design tokens, and example assets. It also provides the Drupal page shell. |

### Canvas editor access

Drupal authenticates local users and assigns access through Drupal roles. Assign a test author the **Content editor** role (`content_editor`). Drupal CMS Starter provides `create canvas_page`, `edit canvas_page`, and `create image media`. The demo recipe grants `publish auto-saves` for publishing Canvas drafts and `use text format canvas_full_html` for CKEditor fields. Setup uses an administrator account. Visitors can view the published page anonymously. Reviewers can use local Drupal accounts for the Canvas authoring demo.

#### Set up a test editor

1. Sign in as the local administrator and open **People → Add user**.
2. Create an active account such as `canvas-reviewer` with a test email address and a password generated locally. Assign **Content editor** as the account's only additional role. Keep the password outside the repository.
3. Sign in as the test editor in a private browser window. Under **Content → Pages**, create a disposable page with an unused alias. Insert the hero and a grid with one card, change the card text, select an image, and publish.
4. Open the disposable page in a signed-out window to confirm the published content is visible. Remove the test page through Drupal when finished.

#### Optional Auth0 sign-in on the Canvas site

The Canvas configuration export includes the Auth0 dashboard module and its
Generic client in strict ID-token validation mode. On a separate Starter
installation, run `ddev drush en sso_dashboards -y` to enable this setup. Canvas
editor access continues to use the Drupal **Content editor** role.

For the bundled Auth0 application, add these URLs in Auth0:

- **Allowed Callback URLs** `https://drupal-canvas-assessment.ddev.site/openid-connect/auth0`
- **Allowed Logout URLs** `https://drupal-canvas-assessment.ddev.site/user/login`

Add the rotated client secret to a one-line `.auth0-client-secret` file in
the Canvas project root. Git ignores this file. In a fresh setup, add the
following to the end of the ignored `web/sites/default/settings.php` file
while it is within PHP:

```php
$auth0_secret_file = dirname($app_root) . '/.auth0-client-secret';
if (is_readable($auth0_secret_file)) {
  $auth0_secret = trim((string) file_get_contents($auth0_secret_file));
  if ($auth0_secret !== '') {
    $config['openid_connect.client.auth0']['settings']['client_secret'] = $auth0_secret;
  }
}
```

Run `ddev drush cr`, then choose **Auth0** at `/user/login`. Configure a
different application at `/admin/config/people/openid-connect`. See the
[Auth0 role mapping guide](#auth0-and-test-users)
for the Post Login Action and test accounts.

### Reusable Canvas controls

Find the theme components under **Library → Components → Specialty Food**. The source is in [theme components](web/themes/custom/sfa_theme/components).

| Component | Controls and composition |
| --- | --- |
| Event hero | Edit **Heading** and **Description** in CKEditor. The heading uses H1 semantics. Use **Shift+Enter** for a line break inside the heading. Configure the background, photo, and event details in the same form. |
| Button link | Add individual buttons to the hero’s **Desktop buttons** and **Mobile buttons** slots. Edit each button’s label, URL, and style on the button itself. When the mobile slot is empty, mobile visitors see the desktop buttons. |
| Container | Defaults to **Boxed**. The other choices are **Full width** and **Full width with boxed content**. Top and bottom padding are independent. Add components to its **Content** slot. |
| Card group | **Item alignment** is the first control and aligns cards and their contents. **Row alignment** positions the row as a group. Editors can also set direction, items across, spacing, and padding, then add or reorder cards in the **Cards** slot. |
| Eyebrow, Title, and Description | Add these reusable atoms directly to any slot. Title and Description offer CKEditor fields. |

### Local Canvas examples

| Page | Path | What it demonstrates |
| --- | --- | --- |
| Winter FancyFaire | `/winter-fancyfaire` | Imported by the demo recipe and set as the front page. |
| Winter FancyFaire Canvas Showcase | `/winter-fancyfaire-canvas` | Authored on the local DDEV site with separate desktop and mobile buttons and a Container wrapping four cards. |
| Audience Resources | `/audience-resources` | Authored on the local DDEV site with standalone typography atoms and a two-card group. |

A fresh install imports Winter FancyFaire. Use the steps below to compose more pages in Canvas.

### Canvas screenshots

- [Published desktop page](screenshots/published-desktop.png)
- [Canvas editor, desktop preview with the nested card layers](screenshots/canvas-editor.png)

### Canvas validation results

| Check | Result |
| --- | --- |
| Canvas authoring | Created the page from scratch, nested four cards in the grid slot, selected assets through the Media Library, and published using the local administrator account. |
| Content editor workflow | A test account with only the Content editor role created a blank page, configured the hero, grid, and a nested card, selected a Media Library image, and published. Anonymous access was denied for the draft and allowed after publication. |
| Anonymous rendering | Confirmed the front page and `/winter-fancyfaire` render the published hero and four cards. |
| Responsive rendering | Checked 1920, 1366, 1024, 768, and 390px in Chromium. Each width rendered without horizontal overflow. At 1920px, the hero was 584px tall and the four cards measured 318px each with 24px gutters. |
| Fresh install and demo import | Installed Canvas from the tracked configuration in an empty DDEV database. Applied the demo recipe and verified the published Winter FancyFaire front page. |

### Compose the design in Canvas

1. Choose **Add Page**. Set the title and URL alias in **Page data**. Use an unused alias when the demo page is already installed.
2. In **Library → Components → Specialty Food**, add **Event hero**. Add **Button link** instances to **Desktop buttons** and optionally to **Mobile buttons**. Then add **Card group** below the hero.
3. Drag **Audience card** into the grid's **Cards** slot. Add three more cards to the same slot and use **Layers** to verify their nesting and order.
4. Edit the four cards using the copy below. Use the component image fields to upload or select the supplied images and icons from the Media Library. The source PNGs are under `web/themes/custom/sfa_theme/assets` and the component `assets` folders. Add descriptive alternative text for card photos.
5. Choose the desktop preview. In **Review changes**, select the page you are editing and publish it. Visit the page in a logged-out browser to inspect the layout without the admin UI.

| Heading | Summary | Link label | Photo and badge |
| --- | --- | --- | --- |
| I'm a Maker | Grow your brand and reach new markets. | See resources for makers | `maker.png`, `icon-maker.png` |
| I'm a Buyer | Discover new products and suppliers. | See resources for buyers | `buyer.png`, `icon-buyer.png` |
| I'm a Distributor | Find tools to grow your business. | See resources for distributors | `distributor.png`, `icon-distributor.png` |
| I'm a Service Provider | Connect with the specialty food community. | See resources for providers | `provider.png`, `icon-provider.png` |

The hero examples supply the reference copy, including the 2028 heading and the January 2027 date. The brief supplied no destination URLs. The sample links use prototype anchors that editors can replace with real destinations. The hero loads the requested Adobe Fonts stylesheet and Font Awesome Free 6.7.2 through external stylesheets. Network access is required for those resources.

See [component-specs.md](component-specs.md) for the component contracts and responsive behavior and [agent-logs.md](agent-logs.md) for the implementation and validation milestones.

### Canvas challenges and blockers

- The installed Canvas schema registry rejected the textarea reference shown in the bundled documentation. The final hero uses Canvas Full HTML with textarea-backed rich-text fields. A soft line break in CKEditor preserves the two-line title.
- During rapid edits, Chromium intermittently logged a Drupal core `once` JavaScript error. The authored content persisted and Canvas published it successfully.
- The verification pass found that the demo export marked Winter FancyFaire as unpublished. The exported page now preserves the published status so the demo can serve as the front page after import.

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

### Install the Auth0 assessment from config

```sh
cd auth0-assessment
ddev start
ddev composer install
ddev drush site:install --existing-config --db-url=mysql://db:db@db/db --account-name=admin -y
ddev launch
ddev drush cr
```

Run the block above from the repository root, so DDEV commands execute inside `auth0-assessment/`. It uses the DDEV project name `drupal-assessment` so the existing Auth0 callback URL still works. If DDEV already knows a different checkout under that name, run `ddev stop --unlist` from the old checkout first.

Run the install command on a new DDEV database. Drupal installs the modules,
the Auth0 client, and both dashboard roles from `config/sync`. Drush prints the
local administrator credentials. Use `ddev describe` to confirm the site URL,
which defaults to `https://drupal-assessment.ddev.site`.

#### Configuration synchronization

Drupal reads the [Auth0 configuration export](auth0-assessment/config/sync) from this project. On a site installed from
this export, run `ddev drush config:status` to see differences and
`ddev drush config:import` to review and import changes. The same screen is
available at **Configuration → Development → Configuration synchronization**
(`/admin/config/development/configuration`).

Drupal requires a matching site UUID to import the full export. Supply the
Auth0 client secret through the ignored runtime file so it stays out of
configuration exports.

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

The screenshots use local Drupal demo accounts and live API data.
[Member dashboard](auth0-assessment/screenshots/member-dashboard.png) ·
[Exhibitor dashboard](auth0-assessment/screenshots/exhibitor-dashboard.png) ·
[Member denied exhibitor](auth0-assessment/screenshots/member-exhibitor-denied.png) ·
[Exhibitor denied member](auth0-assessment/screenshots/exhibitor-member-denied.png) ·
[Anonymous denied](auth0-assessment/screenshots/anonymous-member-denied.png)

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

#### Open before submission

- **Auth0 credential rotation and replay.** During a later fresh setup, Auth0
  returned HTTP 401 during token exchange because Drupal lacked a valid client
  secret. A secret was exposed in the earlier standalone repository. Use a rotated
  secret in `auth0-assessment/.auth0-client-secret`.
  Replay login, dashboard access, and logout with both accounts. The live
  results above describe the earlier successful run.
- **Additional live validation.** Automated tests cover role revocation.
  Live role revocation and the Free-tier `app_metadata` role source still need
  separate checks.

## Submission

Share the [combined repository](https://github.com/yusuf-khan/drupal-canvas-assessment),
branch `main`, and the output of `git rev-parse HEAD` with the interview
team. The two setup guides, [Canvas component specifications](component-specs.md),
[Auth0 module plan](module-plan.md), [AI development log](agent-logs.md), and
the screenshots for both prototypes are in this checkout. Share reviewer
passwords and application secrets privately.
