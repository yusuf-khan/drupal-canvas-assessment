# Specialty Food Canvas components

The supplied 1920 × 1067 reference shows a 584px full-bleed hero and a centered 1344px content area shared by the hero and cards. The card row uses 24px gutters, 16:9 photos, an overlapping circular badge, and bottom-aligned links.

| Canvas component | Editor controls | Layout and behavior |
| --- | --- | --- |
| Event hero | Eyebrow, heading, optional second line, heading level, summary, photo, date, venue, city, outline and filled link labels and URLs | Full-width cream section with a right-aligned decorative image and a soft edge. The image stacks above the text at 900px and below. Each button requires both label and destination. |
| Audience grid | Desktop column count, optional HTML anchor, Cards slot | Editors can add, duplicate, remove and reorder child cards. Two columns at 1100px and below, then one at 580px and below. |
| Audience card | Heading, photo with alternative text, decorative icon, summary, link label and URL | Reusable article with an optional badge and photo. Use four instances for Maker, Buyer, Distributor and Service Provider. Cards stretch to a common height when placed in the grid. |

The component module shares the supplied palette, typography and radius tokens. The theme supplies a minimal Drupal page shell. Canvas owns the page composition and publication state. The event year in the reference heading differs from the event date shown in the reference. Keep both editable and preserve the supplied copy in the prototype.
