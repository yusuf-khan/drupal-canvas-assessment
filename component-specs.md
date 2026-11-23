# Specialty Food Canvas components

The supplied 1920 × 1067 reference shows a 584px full-bleed hero and a centered 1344px content area shared by the hero and cards. The card row uses 24px gutters, 16:9 photos, an overlapping circular badge, and bottom-aligned links.

## Atomic Design structure

Component paths below are relative to `web/themes/custom/sfa_theme`.

| Level | Source and composition |
| --- | --- |
| Atoms | `components/atoms` contains Icon, Button link, Eyebrow, Title, and Description. Button link supports text, outline, and filled styles. Title and Description expose CKEditor fields. |
| Molecules | `components/molecules` contains Event detail, which pairs an icon with one or two lines, and Card badge, which frames a decorative Media Library image. |
| Organisms | `components/organisms` contains the layout components. Event hero uses Eyebrow, Title, Description, and Event detail. Its button slots accept Button link instances. Audience card uses Title, Description, Card badge, and Button link. Card group arranges card instances in its Cards slot. Container wraps any Canvas components in a Content slot. |
| Pages | `demo/sfa-homepage/content/canvas_page` stores the exported Winter FancyFaire composition. The recipe imports this page as the front page. Editors can build and publish additional compositions in Canvas. |

## Organism controls

Formatted headings and descriptions use CKEditor 5 textareas. The Title atom preserves heading semantics when CKEditor wraps the text in a paragraph. Event hero always uses H1 semantics. The standalone Title and Audience card expose their own heading level settings.

Each organism offers White, Cream, and Banana background presets. The hero defaults to Cream. Container, Card group, and Audience card default to White. The hero photo gradient and card badge follow their parent’s background.

| Canvas component | Editor controls | Layout and behavior |
| --- | --- | --- |
| Event hero | Eyebrow, rich-text H1 heading and description, photo, background color, date and location icons, date, venue, city, Desktop buttons and Mobile buttons slots | Full-width cream section with a right-aligned decorative image and a soft edge. The image stacks above the text at 900px and below. Heading is required. Use a soft line break for a two-line title. Each button owns its label, URL, and style. Mobile buttons appear at 640px and below when that slot has content. The desktop slot supplies the fallback. |
| Card group | Item alignment first, followed by direction, items across, gap, row alignment, and top and bottom padding. Background, rich-text section heading, description, anchor, and Cards slot follow the layout controls. | Row layouts use up to two items across at 1100px and below and one at 580px and below. Column direction stacks the cards. Start, Center, and End align each card and its contents. Stretch keeps row cards the same height and aligns their content to the start. Row alignment controls the placement of the cards as a group. |
| Container | Boxed (default), full width, or full width with boxed content, background, top and bottom padding, Content slot | Boxed constrains the container itself. Full width spans the available space. Full width with boxed content applies the width constraint to the inner content. Top and bottom padding each accept 0–160px and default to 32px. |
| Audience card | Heading and H2/H3 level, optional formatted heading and description, background, photo with alternative text, decorative badge icon, summary, and link label and URL | Reusable article with an optional badge and photo. Use four instances for Maker, Buyer, Distributor and Service Provider. Card group stretches cards to a common height by default. |

The theme shares the supplied palette, typography and radius tokens. The theme supplies a minimal Drupal page shell. Canvas owns the page composition and publication state. The event year in the reference heading differs from the event date shown in the reference. Keep both editable and preserve the supplied copy in the prototype.

## Page compositions

| Page | Canvas layout |
| --- | --- |
| Winter FancyFaire at `/winter-fancyfaire` | Published demo recipe. Event hero with the reference copy and two desktop Button links, followed by a four-card group. |
| Winter FancyFaire Canvas Showcase at `/winter-fancyfaire-canvas` | Local page authored in Canvas. Event hero with a CKEditor description and separate desktop and mobile buttons. A Container wraps the four-card group. |
| Audience Resources at `/audience-resources` | Local page authored in Canvas. A Container holds standalone Eyebrow, Title, Description, and Button link components, followed by a two-card group. |

The two local examples demonstrate additional compositions. The demo recipe imports Winter FancyFaire. For existing theme-based installations, [the layout migration](scripts/migrate-canvas-layout-fields.php) converts earlier copy and button fields while preserving page and draft publication state. Follow [the upgrade steps](README.md#update-an-existing-canvas-demo).
