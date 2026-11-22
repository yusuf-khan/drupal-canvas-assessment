# Specialty Food Drupal Canvas assessment

## Environment and dependencies

| Dependency | Version used |
| --- | --- |
| Drupal CMS | 2.2.10 |
| Drupal core | 11.4.13 |
| Drupal Canvas | 1.13.0 |
| PHP | 8.4.27 |
| MariaDB | 11.8 |
| Drush | 13.8.1 |
| DDEV | 1.25.5 |
| Font Awesome Free | 6.7.2 |

The DDEV configuration serves Drupal from `web/`. Composer locks the project dependencies.

## Run locally

1. From the repository root run `ddev start` and `ddev composer install`.
2. On a new site, open `ddev launch`, select the Drupal CMS Starter setup, and create a local administrator.
3. Run `ddev drush pm:enable sfa_components -y`, `ddev drush theme:enable sfa_theme -y`, `ddev drush config:set system.theme default sfa_theme -y`, and `ddev drush cache:rebuild`.
4. Import the demo page and its media with `ddev exec --dir /var/www/html/web php core/scripts/dr recipe /var/www/html/demo/sfa-homepage`. The recipe makes Winter FancyFaire the site front page. It is also available at `/winter-fancyfaire`.
5. Run `ddev drush user:login`, open **Content → Pages**, and edit **Winter FancyFaire** to inspect it in Canvas. The demo recipe grants the Drupal CMS **Content editor** role the Canvas publish permission, supplementing its existing page creation and editing permissions.

If a Drush command fails while traversing the repository, run it from the Drupal docroot with `ddev exec --dir /var/www/html/web /var/www/html/vendor/bin/drush <command>`.

## Module evaluation

| Module or feature | Decision |
| --- | --- |
| Drupal Canvas | Owns page composition, autosaves, previews, and publishing. Its component library lets editors assemble the layout from a blank page. |
| Core Single Directory Components | Defines the hero, grid, and card using Twig, CSS, and property schemas. The grid exposes a slot so editors can add or reorder cards. |
| Core Media and Media Library | Provide the image fields for selecting reusable media and managing photo alternative text. |
| Custom `sfa_components` module | Packages the three components, design tokens, and example assets. The `sfa_theme` theme provides the Drupal page shell. |

## Authentication and role mapping

Drupal authenticates local users and assigns access through Drupal roles. Assign a test author the **Content editor** role (`content_editor`). Drupal CMS Starter provides `create canvas_page`, `edit canvas_page`, and `create image media`. The demo recipe adds `publish auto-saves`, which lets editors publish Canvas drafts they have permission to edit. Setup uses an administrator account. Visitors can view the published page anonymously. This prototype uses local Drupal accounts and manual role assignment.

### Set up a test editor

1. Sign in as the local administrator and open **People → Add user**.
2. Create an active account such as `canvas-reviewer` with a test email address and a password generated locally. Assign **Content editor** as the account's only additional role. Keep the password outside the repository.
3. Sign in as the test editor in a private browser window. Under **Content → Pages**, create a disposable page with an unused alias. Insert the hero and a grid with one card, change the card text, select an image, and publish.
4. Open the disposable page in a signed-out window to confirm the published content is visible. Remove the test page through Drupal when finished.

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
2. In **Library → Components → Specialty Food**, place **Event hero** and then **Audience grid** into the content region.
3. Drag **Audience card** into the grid's **Cards** slot. Add three more cards to the same slot and use **Layers** to verify their nesting and order.
4. Edit the four cards using the copy below. Use the component image fields to upload or select the supplied images and icons from the Media Library. The source PNGs are under `web/modules/custom/sfa_components/assets` and the component `assets` folders. Add descriptive alternative text for card photos.
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

- The installed Canvas schema registry rejected the textarea reference shown in the bundled documentation. The component schemas use plain text inputs and separate fields for intentional line breaks.
- During rapid edits, Chromium intermittently logged a Drupal core `once` JavaScript error. The authored content persisted and Canvas published it successfully.
- The verification pass found that the demo export marked Winter FancyFaire as unpublished. The exported page now preserves the published status so the demo can serve as the front page after import.
