# Specialty Food Canvas components

## Draft for review

The Event hero calendar and location pin are implemented. The authoring changes below are proposed for review before implementation.

Editors supply page content and arrange the cards. The theme owns spacing, widths, alignment, image crops, typography, responsive breakpoints, and icons. The proposed style controls are limited to existing brand presets for Container color and Button link style.

The supplied 1920 × 1067 reference shows a 584px full-bleed hero and a centered 1344px content area shared by the hero and cards. The card row uses 24px gutters, 16:9 photos, an overlapping circular badge, and bottom-aligned links.

## What the current forms expose

- Card group exposes gap from 0 to 80px, top and bottom padding from 0 to 160px, desktop columns, layout direction, and two alignment controls. Container exposes width and the same padding ranges. The proposed forms put these layout rules in CSS.
- Event hero, Card group, Audience card, and Container each expose background color. Audience card also exposes a badge image picker and heading level. The proposed forms keep a single place to select a section background and derive card badges from an approved audience list.
- Audience card and the standalone Title and Description components expose overlapping plain and formatted fields. Card group exposes two descriptions. Each proposed form gives editors a single field for each piece of content.

## Proposed Canvas fields

Use one Heading and one Description where applicable. The Event hero keeps its existing CKEditor fields for copy and supports an intentional break in the two-line title. Use a plain heading and one description textarea for Card group and Audience card. Give each standalone text component one field and allow a line break in standalone Title. The theme renders the hero title as H1 and other headings as H2.

| Component | What editors enter | What the theme controls |
| --- | --- |
| Event hero | Eyebrow, required heading, description, decorative photo, event date, venue, city, and the existing button slots. Each Button link requires a label and destination to render. | Full-bleed Cream hero with a right-aligned photo that stacks above the copy at 900px and below. The template sets the fixed calendar and pin. |
| Card group | Optional section heading, description, and section link ID. Editors add, remove, and reorder Audience cards in the Cards slot. | White background and a start-aligned grid with up to four cards across on desktop, two at 1100px and below, and one at 580px and below. Groups with fewer cards use up to the number of cards present. The theme sets the 24px gutters, section padding, equal card heights per row, and bottom-aligned links. |
| Audience card | Required heading, description, photo with alternative text, and an optional resource link with label and destination. The optional Audience field offers Maker, Buyer, Distributor, and Service Provider. | White card with a 16:9 photo. Each audience maps to its existing branded badge. A card with no audience has no badge. The theme fixes the badge size, position, and card spacing. |
| Container | Content slot and the existing White, Cream, or Banana background presets. | Boxed width, responsive gutters, and 32px vertical padding. White is the default. |
| Button link | Label, destination, and a choice of Text, Outline, or Filled. | The theme defines all three styles. The Audience card resource link uses the Text style. |
| Eyebrow, Title, Description | One text field per component. | Theme typography and a fixed H2 for standalone Title. |

Optional details render when they have content. An optional link renders when both its label and destination have values. The section link ID is for links to a specific card group, such as `#resources` in the demo.

## Implemented brand icons

The Event hero template assigns the calendar to Event date and the pin to Venue and City. The shared icon template renders `fa-solid fa-calendar-days` and `fa-solid fa-location-dot` using the theme’s local Font Awesome Free 6.7.2 assets. The decorative glyphs are hidden from assistive technology and appear only when the corresponding text is present. Editors fill in Event date, Venue, and City. [Event hero template](sfa_theme/components/organisms/event-hero/event-hero.twig) · [Icon markup](sfa_theme/components/atoms/icon/icon.twig) · [Theme library](sfa_theme/sfa_theme.libraries.yml)

## Decisions for review

- The earlier draft proposed plain text for all copy. The hero uses CKEditor with heading and source-editing controls. Review the smallest formatting set the hero needs before changing its toolbar or fields. The supplied two-line title and any authored links need to carry over.
- The earlier draft proposed replacing the two hero button slots with one primary and one secondary link shared across screen sizes. The showcase uses separate mobile buttons. Confirm which labels and destinations to keep before changing the slot design.

## Existing pages and drafts

The theme provides the visual design. Canvas owns composition and publication. Update the demo recipe and migrate existing pages and drafts to the simplified fields. Carry forward copy, intentional line breaks, media, link destinations, section link IDs, card order, and publication state. The event year in the reference heading differs from the event date shown in the reference. Keep both values editable and preserve the supplied copy in the prototype.

Before converting saved layouts, review content entered in duplicate fields. Where both fields contain different copy, retain the version currently rendered and review the other value before removing it. Review any inline links or intentional formatting that the proposed plain-text fields cannot carry. Keep the saved content available until those cases have an approved mapping.

| Page | What to verify |
| --- | --- |
| Winter FancyFaire at `/winter-fancyfaire` | The published demo recipe renders the two reference links and four audience cards. |
| Winter FancyFaire Canvas Showcase at `/winter-fancyfaire-canvas` | The locally authored hero and four-card group open in Canvas and on the public page. Both current button slots retain their content. |
| Audience Resources at `/audience-resources` | The locally authored standalone text and link components and two-card group open in Canvas and on the public page. |

The [existing layout migration](../../../scripts/migrate-canvas-layout-fields.php) documents how previous changes to copy and links handled saved layouts and drafts.

## Acceptance checks

- As a Content editor, edit Event hero, Card group, Audience card, and Container in Canvas. Confirm the forms match the fields above. Check that numeric padding and gap, layout direction, columns, alignment, heading level, and icon selection are absent. Confirm the Container offers the three approved colors and Button link offers its three approved styles.
- Enter a date and location. Confirm the calendar and pin render next to the matching text in the editor preview and public page. Clear the text and confirm the corresponding detail disappears.
- Change the photo and reorder the cards. Compare the demo with the desktop reference and check the layout at tablet and mobile widths. Verify that both hero links remain available and that the page fits without horizontal overflow.
- Verify a fresh demo import and an existing page and draft after migration. Check copy, media, links, card order, and publication state.
