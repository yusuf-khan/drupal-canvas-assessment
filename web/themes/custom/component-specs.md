# Specialty Food Canvas components

## Canvas authoring spec

Editors supply page content and arrange the cards. The theme owns Card group spacing, alignment, and responsive breakpoints. The Card group desktop column count and component background colors use predefined choices.

The supplied 1920 × 1067 reference shows a 584px full-bleed hero and a centered 1344px content area shared by the hero and cards. The card row uses 24px gutters, 16:9 photos, an overlapping circular badge, and bottom-aligned links.

## Canvas fields

| Component | What editors enter | What the theme controls |
| --- | --- |
| Event hero | Background preset, eyebrow, required rich-text heading, rich-text description, decorative photo, event date, venue, city, and two optional link pairs. Enter a label and URL for each Outline or Filled link. | The Outline link comes first and both links appear at all viewport sizes. The hero uses H1 semantics, defaults to Cream, and stacks the photo above the copy at 900px and below. The template sets the fixed calendar and pin. |
| Card group | Two, three, or four columns on desktop, background preset, optional section link ID, and the Cards slot. | A start-aligned row with equal-height cards and 24px gutters. CSS sets two columns at 1100px and below and one column at 580px and below. Desktop padding is 62px top and 65px bottom. At 580px and below both are 32px. |
| Audience card | Background preset, photo with alternative text, optional badge image, required plain heading, plain summary, and an optional resource link with label and URL. | H2 heading, 16:9 photo, fixed badge placement and spacing, and a bottom-aligned resource link. |
| Container | Content slot, width mode, background preset, top padding, and bottom padding. | Defaults to Boxed with 32px vertical padding. |
| Button link | Label, destination, and a choice of Text, Outline, or Filled. | The theme defines all three styles. The Audience card resource link uses the Text style. |
| Eyebrow, Title, Description | Standalone text components. | Theme typography. |

Optional details render when they have content. An optional link renders when both its label and destination have values. The section link ID is for links to a specific card group, such as `#resources` in the demo.

## Implemented brand icons

The Event hero template assigns the calendar to Event date and the pin to Venue and City. The shared icon template renders `fa-solid fa-calendar-days` and `fa-solid fa-location-dot` using the theme’s local Font Awesome Free 6.7.2 assets. The decorative glyphs are hidden from assistive technology and appear only when the corresponding text is present. Editors fill in Event date, Venue, and City. [Event hero template](sfa_theme/components/organisms/event-hero/event-hero.twig) · [Icon markup](sfa_theme/components/atoms/icon/icon.twig) · [Theme library](sfa_theme/sfa_theme.libraries.yml)

## Decisions for review

- The hero uses CKEditor with heading and source-editing controls. Review the smallest formatting set the hero needs before changing its toolbar or fields. The supplied two-line title and any authored links need to carry over.

## Existing pages and drafts

The theme provides the visual design. Canvas owns composition and publication. Update the demo recipe and migrate existing pages and drafts to the simplified fields. Carry forward copy, intentional line breaks, media, link destinations, section link IDs, card order, and publication state. The event year in the reference heading differs from the event date shown in the reference. Keep both values editable and preserve the supplied copy in the prototype.

The migration copies up to two compatible hero links into the new fields. A pair with the same style becomes Outline then Filled in its saved order. Authored Card group introductions become standalone Title and Description components. Audience card headings and summaries retain their displayed copy as plain text. The migration reports incompatible content, such as different links between viewport sizes or embedded links in card copy, and rolls back for review.

| Page | What to verify |
| --- | --- |
| Winter FancyFaire at `/winter-fancyfaire` | The published demo recipe renders the two reference links and four audience cards. |
| Winter FancyFaire Canvas Showcase at `/winter-fancyfaire-canvas` | Check any legacy mobile links against the desktop links before migrating to one pair across viewport sizes. |
| Audience Resources at `/audience-resources` | The locally authored standalone text and link components and two-card group open in Canvas and on the public page. |

Take a database snapshot and run the layout migration after rebuilding Drupal caches, before opening saved pages in Canvas. [Layout migration](../../../scripts/migrate-canvas-layout-fields.php)

## Acceptance checks

- As a Content editor, edit Event hero, Card group, and Audience card in Canvas. Check that Event hero has both link pairs, Card group has only the three listed fields and Cards slot, and Audience card has one heading and one summary.
- Enter a date and location. Confirm the calendar and pin render next to the matching text in the editor preview and public page. Clear the text and confirm the corresponding detail disappears.
- Change the photo and reorder the cards. Compare the demo with the desktop reference and check the layout at tablet and mobile widths. Verify that both hero links remain available and that the page fits without horizontal overflow.
- Verify a fresh demo import and an existing page and draft after migration. Check copy, media, links, card order, and publication state.
