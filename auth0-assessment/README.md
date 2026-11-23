# Drupal Auth0 SSO Assessment

A Drupal 11 prototype with Auth0 sign-in, role-based dashboard access, and
server-side REST and SOAP integrations. OpenAI Codex was the primary coding
agent. See the [reviewed module plan](module-plan.md) and
[AI development log](agent-logs.md) for decisions, review, and validation.

## Architecture

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

## Environment and dependencies

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

## Setup and run

```sh
git clone --branch main https://github.com/yusuf-khan/drupal-canvas-assessment.git
cd drupal-canvas-assessment/auth0-assessment
ddev start
ddev composer install
ddev drush site:install --existing-config --db-url=mysql://db:db@db/db --account-name=admin -y
ddev launch
ddev drush cr
```

Run these commands from the Auth0 project directory. It uses the DDEV project name `drupal-assessment` so the existing Auth0 callback URL still works. If DDEV already knows a different checkout under that name, run `ddev stop --unlist` from the old checkout first.

Run the install command on a new DDEV database. Drupal installs the modules,
the Auth0 client, and both dashboard roles from `config/sync`. Drush prints the
local administrator credentials. Use `ddev describe` to confirm the site URL,
which defaults to `https://drupal-assessment.ddev.site`.

### Configuration synchronization

Drupal reads `config/sync` from the repository root. On a site installed from
this export, run `ddev drush config:status` to see differences and
`ddev drush config:import` to review and import changes. The same screen is
available at **Configuration → Development → Configuration synchronization**
(`/admin/config/development/configuration`).

Drupal requires a matching site UUID to import the full export. Supply the
Auth0 client secret through the ignored runtime file so it stays out of
configuration exports.

## Module evaluation

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

## Auth0 and test users

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
   [post-login-roles.js](auth0/post-login-roles.js). Replace the client ID
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
`web/sites/default/settings.secret.php` supplies the public Client ID for the
bundled Auth0 application. Create `.auth0-client-secret` at the repository root
and put the current Auth0 Client Secret on a single line. Git ignores this
file. Run `ddev drush cr` after updating it.

For a different Auth0 application, update the public Client ID in
`web/sites/default/settings.secret.php`. For a different tenant, also update
the issuer and endpoints at `/admin/config/people/openid-connect`.

### Reviewer test accounts

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

## Role mapping and dashboards

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
[`sso_dashboards.settings`](web/modules/custom/sso_dashboards/config/install/sso_dashboards.settings.yml).

### External APIs

- The member dashboard sends a server-side `GET /users/1` to JSONPlaceholder.
  It displays the fictional user's name, email, and company. [REST endpoint](https://jsonplaceholder.typicode.com/users/1)
- The exhibitor dashboard sends a SOAP `POST` for `ListOfCountryNamesByName`
  to Beeceptor's `CountryInfoService.wso` endpoint and displays country names
  and ISO codes. [Beeceptor SOAP documentation](https://app.beeceptor.com/virtual-sandbox/soap-service-free)

Both public HTTPS services require no API key. Drupal caches validated results
for 60 seconds and renders the signed-in user's identity on each response.

## Validation results

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
[Member dashboard](screenshots/member-dashboard.png) ·
[Exhibitor dashboard](screenshots/exhibitor-dashboard.png) ·
[Member denied exhibitor](screenshots/member-exhibitor-denied.png) ·
[Exhibitor denied member](screenshots/exhibitor-member-denied.png) ·
[Anonymous denied](screenshots/anonymous-member-denied.png)

## Challenges and blockers

### Resolved during development

- **Missing roles after sign-in.** The deployed Auth0 Action had to be attached
  to the Post Login flow. Applying the binding restored the role claim.
- **Fresh install failed with duplicate configuration.** OpenID Connect owns
  `openid_connect.settings`. The custom module applies its defaults in an
  install hook, and the fresh install from `config/sync` passed.
- **Missing External Auth table.** Stale local module registrations left the
  `authmap` table missing. The local database was repaired, and fresh installs
  create the table automatically.

### Open before submission

- **Auth0 credential rotation and replay.** During a later fresh setup, Auth0
  returned HTTP 401 during token exchange because Drupal lacked a valid client
  secret. A secret was committed to the earlier standalone repository. Rotate it in Auth0, remove it from the
  tracked settings, and store the replacement in `.auth0-client-secret`.
  Replay login, dashboard access, and logout with both accounts. The live
  results above describe the earlier successful run.
- **Additional live validation.** Automated tests cover role revocation.
  Live role revocation and the Free-tier `app_metadata` role source still need
  separate checks.

## Submission

The submission branch is `main`. Run `git rev-parse HEAD` on the final
reviewed commit, then share that SHA and the repository URL privately with
the interview team. Confirm reviewers can access the repository.
[Combined assessment overview](../README.md)
