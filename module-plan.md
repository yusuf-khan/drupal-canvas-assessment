# module-plan.md: Custom Module Implementation Plan

The Auth0 prototype lives in `auth0-assessment/`. Paths to module code,
configuration, and commands in this plan are relative to that project.

**Status** — the assessment runs on OpenID Connect. The sections below
plan a separate miniOrange SAML cutover. The public edition passed an
isolated Drupal 11 installation test, and section 10 records the requirements
for a full SAML sign-in and logout flow.

The current OIDC implementation and the proposed SAML cutover are separate
validation milestones. See the [README](README.md#auth0-validation-results) for the
working setup and recorded results.

## 1. Goal and scope

Build one small custom module, `sso_dashboards`, that integrates with miniOrange SAML and provides:

1. Reconciliation of Auth0 SAML role attributes with Drupal roles `member` / `exhibitor` at login.
2. Post-login redirect by role.
3. Two role-gated dashboard routes.
4. Server-side REST and SOAP clients whose results are rendered on the dashboards.

Out of scope: visual design, Auth0 user provisioning UI, any profile sync beyond what the contrib module already does.

## 2. Division of responsibility

| Concern | Owner |
| --- | --- |
| SAML sign-in, signed assertion validation, account provisioning, logout | miniOrange SAML Service Provider with the required features enabled |
| Include role names in the signed assertion | Auth0 SAML2 Web App add-on and Post Login Action |
| SAML role reconciliation | miniOrange mapping, verified against the role reconciliation rules below |
| Redirect, routes, access, API calls, rendering | `sso_dashboards` |
| Drupal roles `member` / `exhibitor` | Optional config (`user.role.*.yml`) installed when the role IDs are absent |

`externalauth` is a transitive dependency of `openid_connect` for subject-to-user linking. This applies to the current implementation. The miniOrange cutover removes the OpenID Connect hooks, services, and dependencies after account continuity and logout are verified. Keep authentication and assertion validation in the contributed module.

## 3. Auth0 contract (input to the module)

- Roles are managed in Auth0 (RBAC roles assigned to users, or `app_metadata.roles`; decision in section 9).
- The SAML Post Login Action sends role names as the multi-value attribute `https://sso-prototype.example/roles`.
- Configure the attribute name in miniOrange role mapping. Roles may be granted only from a successfully validated assertion for the configured Auth0 issuer and Drupal service provider.
- Auth0 plan limitations (free tier, Actions availability, trial-only features) are recorded in README.md.

## 4. Module structure

```
sso_dashboards/
  sso_dashboards.info.yml
  sso_dashboards.routing.yml
  sso_dashboards.services.yml
  config/install/
    sso_dashboards.settings.yml
  config/optional/
    user.role.member.yml
    user.role.exhibitor.yml
  config/schema/sso_dashboards.schema.yml
  src/
    Hook/LoginHooks.php
    Controller/DashboardController.php
    Service/RoleMapper.php
    Service/DashboardData.php
    Service/JsonPlaceholderClient.php
    Service/CountryInfoSoapClient.php
  tests/src/{Unit,Kernel,Functional}/...
```

The module targets Drupal 11. The included DDEV environment runs PHP 8.4.

## 5. Components

### 5.1 Role mapping

- Configure miniOrange to map the validated roles attribute. Verify these behaviors on first and subsequent sign-ins:
  - Allow-list: only the Drupal roles `member` and `exhibitor` are managed. Configure the Auth0 role names accepted for each Drupal role. Unmapped claim values are ignored.
  - Add managed roles present in the claim; **remove** managed roles absent from the claim (so revoking in Auth0 revokes in Drupal on next login). Never touch other roles (e.g. `administrator`).
  - Save only if the role set changed.
- No role in claim: user is logged in with no managed role and lands on a neutral page (see 5.2), and is denied both dashboards.
- Both roles in claim: allowed, but redirect priority is defined (default `exhibitor` > `member`, configurable). Access is still per-route.

### 5.2 Post-login redirect

- After login, send `member` to `/dashboard/member`, `exhibitor` to `/dashboard/exhibitor`, and role-less users to `/` with a short message.
- Set the destination through a supported miniOrange extension point after successful SAML sign-in. Verify that the installed edition can select different destinations for different Drupal roles.
- The local username-and-password form also redirects users with a dashboard role. Local accounts without a dashboard role keep the Drupal profile destination. Explicit local destinations take precedence.

### 5.3 Routes and access control

```yaml
sso_dashboards.member:
  path: '/dashboard/member'
  defaults: { _controller: '...DashboardController::member', _title: 'Member dashboard' }
  requirements: { _role: 'member' }

sso_dashboards.exhibitor:
  path: '/dashboard/exhibitor'
  defaults: { _controller: '...DashboardController::exhibitor', _title: 'Exhibitor dashboard' }
  requirements: { _role: 'exhibitor' }
```

- Core's `_role` requirement enforces access on every request including direct URLs; no custom access checker needed.
- Anonymous: HTTP 403.
- Wrong role: 403.
- Note: uid 1 / administrators are denied unless they hold the role. This is intentional ("only") and documented.

### 5.4 REST client (member dashboard)

- `JsonPlaceholderClient::getUser(int $id = 1): array` using Drupal's `http_client` service (Guzzle).
- `GET https://jsonplaceholder.typicode.com/users/1`, timeout 5s, `connect_timeout` 3s.
- Return a normalized array: `name`, `email`, `company.name`. Validate keys exist; otherwise throw a typed exception.
- Base URL in config, not hard-coded in the class.

### 5.5 Beeceptor SOAP client (exhibitor dashboard)

- **Approach:** build the SOAP 1.1 envelope by hand, `POST` via `http_client`, parse with `\DOMDocument`/`SimpleXMLElement`. Avoids a hard dependency on `ext-soap` and gives deterministic control over a mock service. Alternative (`\SoapClient` with the WSDL) noted in section 9.
- POST to the Beeceptor `/CountryInfoService.wso` endpoint using the WSDL’s `ListOfCountryNamesByName` operation, request namespace, and SOAPAction. Parse the original CountryInfo namespace used by Beeceptor’s live response.
- Parse with namespace-aware XPath; return list of `{name, isoCode}`.
- Disable external entity loading when parsing (no `LIBXML_NOENT`); treat the response as untrusted.
- Content-Type `text/xml; charset=utf-8`, same timeouts as REST.

### 5.6 Controller and rendering

- `DashboardController::member()` / `::exhibitor()` return render arrays: a heading, a definition list (member) or an `<ul>`/table (exhibitor). Plain text is enough.
- Show the Drupal username and role at the top for validation screenshots.
- Error handling: catch client exceptions, log to the `sso_dashboards` channel, and render a friendly "service unavailable" message with HTTP 200.
- Caching: share only validated public results with a 60-second Drupal cache lifetime. Render the current user identity on each request. Set `cache_max_age` to 0 to bypass the data cache.
- Escape all remote data (render via `#plain_text` / Twig autoescape).

## 6. Configuration and secrets

- The bundled Auth0 issuer and public Client ID are application settings. Supply the client secret in the ignored `.auth0-client-secret` file outside the Drupal document root.
- Ship portable defaults and role definitions in the module’s `config/install` and `config/optional` directories. The full-site export in `config/sync` supports installing the assessment with its site configuration.
- `sso_dashboards.settings`: `client_id`, `role_claim`, `role_mapping`, `redirect_priority`, `rest_base_url`, `soap_endpoint`, `cache_max_age`.

## 7. Testing and validation

**Automated**
- Unit: `RoleMapper` (add/remove/ignore unknown/idempotent), REST and SOAP response parsing using fixture payloads (including malformed/missing fields).
- Kernel: routing access (`_role`) for anonymous / member / exhibitor / both.
- Functional: verify fresh-install login button placement and local login redirects, then check dashboard rendering, access, shared-cache reuse, and API errors with HTTP fixtures.

**Manual (README.md validation table, with screenshots)**

| # | Check | Expected |
| --- | --- | --- |
| 1 | Member logs in via Auth0 | Drupal user has `member`; lands on `/dashboard/member` |
| 2 | Exhibitor logs in | Has `exhibitor`; lands on `/dashboard/exhibitor` |
| 3 | Member requests `/dashboard/exhibitor` directly | 403 |
| 4 | Exhibitor requests `/dashboard/member` directly | 403 |
| 5 | Anonymous requests either route | Denied |
| 6 | Member page | Name, email, company from live JSONPlaceholder call |
| 7 | Exhibitor page | Country names and ISO codes from live SOAP call |
| 8 | Logout | Drupal and Auth0 session ended; dashboards denied afterwards |
| 9 | Revoke role in Auth0, log in again | Drupal role removed |
| 10 | Upstream down (simulate) | Friendly error, no fatal |

## 8. Delivery milestones

1. Environment: Drupal 11, Auth0 tenant, two test users and roles, contrib module installed and login working.
2. Assertion: The Auth0 Action adds the SAML roles attribute. Verify it reaches Drupal without logging the assertion.
3. Module skeleton, roles, routes and access; redirect.
4. Verify miniOrange role mapping, dashboard redirects, and SAML logout.
5. REST client and member dashboard.
6. SOAP client and exhibitor dashboard.
7. Tests, validation run, screenshots, docs.

## 9. Implementation decisions

1. **Contrib module:** miniOrange SAML SP with assertion-based role mapping and Single Logout.
2. **Role source in Auth0:** Assigned role names read from `event.authorization.roles` and copied to a namespaced SAML attribute in a Post Login Action. Administrator-managed `app_metadata.roles` is an alternative when role assignments are unavailable.
3. **Anonymous behavior:** HTTP 403.
4. **SOAP method:** SOAP 1.1 envelope over Drupal’s Guzzle client, with namespace-aware DOM parsing.
5. **Dual-role users:** Both routes are available. Default redirect priority is exhibitor, then member.
6. **Administrators:** Dashboard access still requires the specific dashboard role.
7. **Caching:** 60 seconds for shared upstream data by default. A zero TTL bypasses the data cache for demonstrations.

## 10. miniOrange SAML pilot with Auth0

The selected pilot uses Auth0 as the SAML 2.0 identity provider and
miniOrange SAML Service Provider (`miniorange_saml` 3.2.1) in Drupal 11.
The Composer edition was evaluated on an isolated site. Source inspection
confirmed that it can assign one default Drupal role to new users. The
assertion-based mapping controls are disabled in that edition. miniOrange
lists custom role mapping and SAML Single Logout under Premium. The
assessment’s Composer dependencies omit miniOrange.
[Specbee walkthrough](https://www.specbee.com/blogs/how-to-integrate-auth0-single-sign-on-sso-in-drupal) ·
[Drupal project](https://www.drupal.org/project/miniorange_saml) ·
[miniOrange plan comparison](https://plugins.miniorange.com/drupal-saml-single-sign-on-sso#pricing)

Use a separate Auth0 application and a disposable Drupal installation for
the pilot. Enable the SAML2 Web App add-on and exchange Auth0 IdP metadata
and Drupal SP metadata. The Composer edition publishes `/saml_metadata`,
starts sign-in at `/samllogin`, and receives assertions at `/samlassertion`.
Use the URLs and entity ID displayed on the miniOrange setup screen.
Require signed assertions and verify issuer, audience, and destination.
[Auth0 SAML2 Web App add-on](https://auth0.com/docs/authenticate/protocols/saml/saml-sso-integrations/enable-saml2-web-app-addon)

The [SAML Post Login Action](auth0-assessment/auth0/post-login-saml-roles.js) sends the role
names as a SAML attribute. Set its client ID to the SAML application's ID.
The same Action can read administrator-managed `app_metadata.roles` on
tenants where native role assignments are unavailable.
[Auth0 SAML attribute mapping in Actions](https://support.auth0.com/center/s/article/Port-a-SAML-configuration-mapping-rule-to-actions)

Before cutover:

1. Obtain the miniOrange edition that supports assertion-based role mapping
   and SAML logout. Map the roles attribute to Drupal's `member` and
   `exhibitor` roles. Check that removing a role at Auth0 also removes the
   Drupal role without removing unrelated roles.
2. Verify the edition's supported redirect hook or role-based destination
   feature. Reuse the existing dashboard priority and route access rules.
3. Review account matching before enabling the live callback. The Composer
   edition looks up Drupal accounts by the asserted username. Verify how the
   licensed edition identifies existing users and avoid enabling automatic
   linking to privileged local accounts. Test both existing Auth0 accounts
   for account continuity.
4. Replay member and exhibitor sign-in, the role-based redirects, wrong-role
   and anonymous access, logout, and revocation. Cutover requires an Auth0
   SAML application and live validation with the licensed edition. The
   assessment continues to use its verified OpenID Connect path meanwhile.
5. After the SAML checks pass, remove the custom module’s `openid_connect`
   dependency, OIDC hooks and configuration, and replace the OIDC-specific
   tests and setup instructions. Uninstall OpenID Connect in Drupal before
   removing its Composer package. Remove External Auth only if Drupal reports
   that no installed module requires it and the existing account links have
   been preserved.
