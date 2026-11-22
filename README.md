# Specialty Food Drupal Canvas assessment

Drupal CMS 2.2.10 project with Drupal core 11.4.13, Canvas 1.13.0, PHP 8.4, MariaDB 11.8, and Drush 13.8.1. DDEV 1.25.5 was used locally. Composer locks the PHP dependencies.

## Run locally

1. From the repository root run `ddev start` and `ddev composer install`.
2. On a new site, open `ddev launch`, select the Drupal CMS Starter setup, and create a local administrator.
3. Run `ddev drush pm:enable sfa_components -y`, `ddev drush theme:enable sfa_theme -y`, `ddev drush config:set system.theme default sfa_theme -y`, and `ddev drush cache:rebuild`.
4. Import the demo page and its media with `ddev exec --dir /var/www/html/web php core/scripts/dr recipe /var/www/html/demo/sfa-homepage`. The recipe makes Winter FancyFaire the site front page. It is also available at `/winter-fancyfaire`.
5. Run `ddev drush user:login`, open **Content → Pages**, and edit **Winter FancyFaire** to inspect it in Canvas. The demo recipe grants the Drupal CMS **Content editor** role the Canvas publish permission, supplementing its existing page creation and editing permissions.

If a Drush command fails while traversing the repository, run it from the Drupal docroot with `ddev exec --dir /var/www/html/web /var/www/html/vendor/bin/drush <command>`.

## Screenshots

- [Published desktop page](screenshots/published-desktop.png)
- [Canvas editor, desktop preview with the nested card layers](screenshots/canvas-editor.png)

The published page was checked at 1920, 1366, 1024, 768, and 390 pixels. The 1920px build matches the reference's hero height, content start, card width, and gutters. Typography and image details can vary slightly across browsers.

## Compose the design in Canvas

1. Choose **Add Page**. Set the title and URL alias in **Page data**. Use an unused alias when the demo page is already installed.
2. In **Library → Components → Specialty Food**, place **Event hero** and then **Audience grid** into the content region.
3. Drag **Audience card** into the grid's **Cards** slot. Add three more cards to the same slot and use **Layers** to verify their nesting and order.
4. Edit the four cards using the copy below. Use the component image fields to upload or select the supplied images and icons from the Media Library. The source PNGs are under `web/modules/custom/sfa_components/assets` and the component `assets` folders. Add descriptive alternative text for card photos.
5. Choose the desktop preview and review and publish the changes. Visit the page in a logged-out browser to inspect the layout without the admin UI.

| Heading | Summary | Link label | Photo and badge |
| --- | --- | --- | --- |
| I'm a Maker | Grow your brand and reach new markets. | See resources for makers | `maker.png`, `icon-maker.png` |
| I'm a Buyer | Discover new products and suppliers. | See resources for buyers | `buyer.png`, `icon-buyer.png` |
| I'm a Distributor | Find tools to grow your business. | See resources for distributors | `distributor.png`, `icon-distributor.png` |
| I'm a Service Provider | Connect with the specialty food community. | See resources for providers | `provider.png`, `icon-provider.png` |

The hero examples supply the reference copy, including the 2028 heading and the January 2027 date. The brief supplied no destination URLs. The sample links use prototype anchors that editors can replace with real destinations. The hero loads the requested Adobe Fonts stylesheet and Font Awesome Free 6.7.2 through external stylesheets. Network access is required for those resources.

See [component-specs.md](component-specs.md) for the component contracts and responsive behavior and [agent-logs.md](agent-logs.md) for the implementation and validation milestones.

During rapid edits, Chromium intermittently logged a Drupal core `once` JavaScript error. The authored content persisted and Canvas published it successfully. The demo recipe was applied on the existing Drupal CMS Starter installation.
