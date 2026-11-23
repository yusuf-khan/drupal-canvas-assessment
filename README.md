# Drupal practical assessments

Both prototypes are available in this repository. Each has its own Drupal
installation, dependencies, and DDEV configuration.

| Assessment | Project directory | Reviewer guide |
| --- | --- | --- |
| Drupal CMS, SDC, and Canvas | Repository root | [Canvas setup](#install-from-scratch) |
| Drupal 11 and Auth0 SSO | `auth0-assessment/` | [Auth0 setup and validation](auth0-assessment/README.md) |

Run DDEV commands from the directory for the assessment you are testing.

## Specialty Food Drupal Canvas assessment

## Environment and dependencies

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

## Install from scratch

This walkthrough installs the Canvas assessment at the repository root. For the second Drupal site, follow the [Auth0 assessment install guide](auth0-assessment/README.md#setup-and-run) from `auth0-assessment/`.

### 1. Clone the project and start DDEV

Install Git, Docker, and DDEV 1.25 or later. Start Docker, then run:

```bash
git clone --branch main https://github.com/yusuf-khan/drupal-canvas-assessment.git
cd drupal-canvas-assessment
ddev start
ddev composer install
ddev drush site:install --existing-config --db-url=mysql://db:db@db/db --account-name=admin -y
ddev launch
ddev drush cr

```

Run the remaining Canvas commands from this directory. Use `ddev describe` to find the local URL if the browser does not open automatically.

### 2. Complete the Drupal CMS installer

At the local URL, choose the Drupal CMS **Starter** site template and create a local administrator. Wait for the installer to finish. Check that Drush can reach the installed site:

```bash
ddev drush status
```

### 3. Enable the theme and install the demo

```bash
ddev drush theme:enable sfa_theme -y
ddev drush config:set system.theme default sfa_theme -y
ddev drush cache:rebuild
ddev exec --dir /var/www/html/web php core/scripts/dr recipe /var/www/html/demo/sfa-homepage
```

The recipe enables Canvas Full HTML, imports the published Winter FancyFaire page and its media, sets it as the front page, and grants the **Content editor** role access to Canvas publishing and CKEditor.

If a Drush command fails while traversing the repository, run it from the Drupal docroot using `ddev exec --dir /var/www/html/web /var/www/html/vendor/bin/drush <command>`.

### 4. Set the configuration sync directory

In your local `web/sites/default/settings.php`, add or replace this setting within the existing PHP block.

```php
$settings['config_sync_directory'] = dirname($app_root) . '/config/sync';
```

```bash
ddev drush cache:rebuild
ddev drush status --fields=config-sync
```

DDEV should report `/var/www/html/config/sync`. The settings file is ignored by Git. The tracked [Canvas configuration export](config/sync) supports synchronization between installations with a matching site UUID. The fresh install already has its page and media after the recipe step.

### 5. Open the published page and Canvas

1. Run `ddev launch` and open the local site in a signed-out window. The front page and `/winter-fancyfaire` should show the event hero and all four cards.
2. Run `ddev drush user:login` and open the generated link to sign in as the local administrator.
3. Go to **Content → Pages → Winter FancyFaire → Edit**. In **Layers**, expand **Event hero → Desktop buttons** or **Card group → Cards** and select a component to inspect its settings.

The Canvas demo uses local Drupal accounts. Follow [Set up a test editor](#set-up-a-test-editor) and [Compose the design in Canvas](#compose-the-design-in-canvas) to verify the authoring workflow with the **Content editor** role.

## Install the Auth0 assessment from config

After cloning the repository, run these commands from the repository root. The install command initializes the Auth0 site from its tracked configuration. Use it on a fresh DDEV database.

```bash
cd auth0-assessment
ddev start
ddev composer install
ddev drush site:install --existing-config --db-url=mysql://db:db@db/db --account-name=admin -y
ddev launch
```

The Auth0 site's settings already point to `auth0-assessment/config/sync`. Follow the [Auth0 setup and test-user guide](auth0-assessment/README.md#auth0-and-test-users) to configure the identity provider and exercise sign-in. For the Canvas site at the repository root, use the [Drupal CMS Starter and demo recipe steps](#install-from-scratch).

## Configuration synchronization

Use `ddev drush config:status` to review differences against the tracked export. Run `ddev drush config:import` on an installation with the same site UUID. A fresh Starter installation has its own UUID. The demo recipe provides the Canvas page and media as content.

The export leaves the Auth0 client secret and notification address blank. If you enable [optional Auth0 sign-in](#optional-auth0-sign-in-on-the-canvas-site), supply the client secret through the ignored runtime file. Set any site-specific notification address locally. The Auth0 assessment uses its own [configuration export](auth0-assessment/config/sync).

## Update an existing Canvas demo

For an existing installation that already uses the `sfa_theme` components, rebuild caches and run the migration to update its saved layouts and drafts.

```bash
ddev drush cache:rebuild
ddev exec --dir /var/www/html/web /var/www/html/vendor/bin/drush php:script /var/www/html/scripts/migrate-canvas-layout-fields.php
ddev drush cache:rebuild
```

The script converts older headings and descriptions to rich text, moves earlier hero buttons into an empty Desktop buttons slot, and removes the retired heading level setting.

## Module evaluation

| Module or feature | Decision |
| --- | --- |
| Drupal Canvas | Owns page composition, autosaves, previews, and publishing. Its component library lets editors assemble the layout from a blank page. |
| Core Single Directory Components | Defines reusable typography and button atoms, the Event hero, Container, Card group, and Audience card. Canvas slots let editors compose and reorder them. |
| Canvas Full HTML | Provides the CKEditor 5 text format used by rich-text headings and descriptions. |
| Core Media and Media Library | Provide the image fields for selecting reusable media and managing photo alternative text. |
| Custom `sfa_theme` theme | Packages the Atomic Design components, design tokens, and example assets. It also provides the Drupal page shell. |

## Authentication and role mapping

Drupal authenticates local users and assigns access through Drupal roles. Assign a test author the **Content editor** role (`content_editor`). Drupal CMS Starter provides `create canvas_page`, `edit canvas_page`, and `create image media`. The demo recipe grants `publish auto-saves` for publishing Canvas drafts and `use text format canvas_full_html` for CKEditor fields. Setup uses an administrator account. Visitors can view the published page anonymously. Reviewers can use local Drupal accounts for the Canvas authoring demo.

### Set up a test editor

1. Sign in as the local administrator and open **People → Add user**.
2. Create an active account such as `canvas-reviewer` with a test email address and a password generated locally. Assign **Content editor** as the account's only additional role. Keep the password outside the repository.
3. Sign in as the test editor in a private browser window. Under **Content → Pages**, create a disposable page with an unused alias. Insert the hero and a grid with one card, change the card text, select an image, and publish.
4. Open the disposable page in a signed-out window to confirm the published content is visible. Remove the test page through Drupal when finished.

### Optional Auth0 sign-in on the Canvas site

The Canvas project can also run the Auth0 dashboard module. After installing
Drupal CMS, run `ddev drush en sso_dashboards -y` from the repository root.
The module enables OpenID Connect, creates the two dashboard roles, and
installs the Generic client in strict ID-token validation mode. Canvas
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
[Auth0 role mapping guide](auth0-assessment/README.md#auth0-and-test-users)
for the Post Login Action and test accounts.

## Reusable Canvas controls

Find the theme components under **Library → Components → Specialty Food**. The source is in [theme components](web/themes/custom/sfa_theme/components).

| Component | Controls and composition |
| --- | --- |
| Event hero | Edit **Heading** and **Description** in CKEditor. The heading uses H1 semantics. Use **Shift+Enter** for a line break inside the heading. Configure the background, photo, and event details in the same form. |
| Button link | Add individual buttons to the hero’s **Desktop buttons** and **Mobile buttons** slots. Edit each button’s label, URL, and style on the button itself. When the mobile slot is empty, mobile visitors see the desktop buttons. |
| Container | Defaults to **Boxed**. The other choices are **Full width** and **Full width with boxed content**. Top and bottom padding are independent. Add components to its **Content** slot. |
| Card group | **Item alignment** is the first control and aligns cards and their contents. **Row alignment** positions the row as a group. Editors can also set direction, items across, spacing, and padding, then add or reorder cards in the **Cards** slot. |
| Eyebrow, Title, and Description | Add these reusable atoms directly to any slot. Title and Description offer CKEditor fields. |

## Local Canvas examples

| Page | Path | What it demonstrates |
| --- | --- | --- |
| Winter FancyFaire | `/winter-fancyfaire` | Imported by the demo recipe and set as the front page. |
| Winter FancyFaire Canvas Showcase | `/winter-fancyfaire-canvas` | Authored on the local DDEV site with separate desktop and mobile buttons and a Container wrapping four cards. |
| Audience Resources | `/audience-resources` | Authored on the local DDEV site with standalone typography atoms and a two-card group. |

A fresh install imports Winter FancyFaire. Use the steps below to compose more pages in Canvas.

## Screenshots

- [Published desktop page](screenshots/published-desktop.png)
- [Canvas editor, desktop preview with the nested card layers](screenshots/canvas-editor.png)

## Validation results

| Check | Result |
| --- | --- |
| Canvas authoring | Created the page from scratch, nested four cards in the grid slot, selected assets through the Media Library, and published using the local administrator account. |
| Content editor workflow | A test account with only the Content editor role created a blank page, configured the hero, grid, and a nested card, selected a Media Library image, and published. Anonymous access was denied for the draft and allowed after publication. |
| Anonymous rendering | Confirmed the front page and `/winter-fancyfaire` render the published hero and four cards. |
| Responsive rendering | Checked 1920, 1366, 1024, 768, and 390px in Chromium. Each width rendered without horizontal overflow. At 1920px, the hero was 584px tall and the four cards measured 318px each with 24px gutters. |
| Demo export and import | Exported the page and its media and applied the demo recipe on the existing Drupal CMS Starter installation. |

## Compose the design in Canvas

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

## Challenges and blockers

- The installed Canvas schema registry rejected the textarea reference shown in the bundled documentation. The final hero uses Canvas Full HTML with textarea-backed rich-text fields. A soft line break in CKEditor preserves the two-line title.
- During rapid edits, Chromium intermittently logged a Drupal core `once` JavaScript error. The authored content persisted and Canvas published it successfully.
- The verification pass found that the demo export marked Winter FancyFaire as unpublished. The exported page now preserves the published status so the demo can serve as the front page after import.
