# Specialty Food Canvas components

The supplied 1920 × 1067 reference shows a 584px full-bleed hero and a centered 1344px content area shared by the hero and cards. The card row uses 24px gutters, 16:9 photos, an overlapping circular badge, and bottom-aligned links.

## Atomic Design structure

Component paths below are relative to `web/themes/custom/sfa_theme`.

| Level | Source and composition |
| --- | --- |
| Atoms | `components/atoms` contains Icon, Button link, Eyebrow, Title, and Description. Button link supports text, outline, and filled styles. Title and Description expose CKEditor fields. |
| Molecules | `components/molecules` contains Event detail, which pairs an icon with one or two lines, and Card badge, which frames a decorative Media Library image. |
| Organisms | `components/organisms` contains the layout components. Event hero uses Event detail and Action link. Audience card uses Card badge and Action link. Card group arranges card instances in its Cards slot. Container wraps any Canvas components in a Content slot. |
| Pages | `demo/sfa-homepage/content/canvas_page` stores the exported Winter FancyFaire composition. Editors can build equivalent or different pages in Canvas. |

## Organism controls

Formatted headings and descriptions use CKEditor 5 textareas. The Title atom gives formatted content the selected heading level, including when CKEditor wraps the text in a paragraph.

Each organism offers White, Cream, and Banana background presets. The hero defaults to Cream, and the grid and cards default to White. The hero photo gradient and card badge follow their parent’s background.

| Canvas component | Editor controls | Layout and behavior |
| --- | --- | --- |
| Event hero | Eyebrow, rich-text H1 heading and description, photo, background color, date and location icons, date, venue, city, Desktop buttons and Mobile buttons slots | Full-width cream section with a right-aligned decorative image and a soft edge. The image stacks above the text at 900px and below. Button link instances can be added and reordered independently. An empty mobile slot uses desktop buttons. |
| Card group | Background, heading and description, direction, gap, items across, top and bottom padding, item alignment, row alignment, anchor, Cards slot | Row layouts use up to two items across at 1100px and below and one at 580px and below. Column direction stacks the cards. Item alignment positions individual cards and their content. Row alignment positions the group of cards along the row. |
| Container | Boxed (default), full width, or full width with boxed content, background, top and bottom padding, Content slot | Wraps any Canvas component. |
| Audience card | Heading and heading level, background color, photo with alternative text, decorative icon, summary, link label and URL | Reusable article with an optional badge and photo. Use four instances for Maker, Buyer, Distributor and Service Provider. Cards stretch to a common height when placed in the grid. |

The theme shares the supplied palette, typography and radius tokens. The theme supplies a minimal Drupal page shell. Canvas owns the page composition and publication state. The event year in the reference heading differs from the event date shown in the reference. Keep both editable and preserve the supplied copy in the prototype.
