# AI-assisted development log

| Milestone | Direction and contribution | Review and validation |
| --- | --- | --- |
| Read the brief and design | Codex inspected the assignment ZIP, examined the desktop reference, and mapped it to a hero, a grid with a slot, and one reusable card. | The design positions and dimensions in the specification come from the supplied 1920px reference. |
| Build the components | Codex implemented Twig, SDC prop schemas, shared tokens, responsive CSS, and a minimal Drupal theme. | The same card accepts all four audience variants. Calls to action, copy, media, and card order are Canvas inputs. |
| Test the Canvas integration | Drupal exposed a mismatch between the bundled Canvas documentation and the installed schema registry for the textarea type. Codex changed the hero's intended line breaks to independent editor fields and kept the summaries as plain text. | Drupal cache rebuild succeeded and the Canvas library displayed all three components. |
| Author and publish | Codex used Chromium to add a blank Canvas page, insert the hero and grid, drag a card into the slot, insert three nested siblings, and select six assets through Media Library controls. | The published route returned HTTP 200 in a logged-out session with all four editable cards and nine rendered assets. The page and its media were exported through Drupal's default content tooling. |
| Compare the desktop render | Codex checked rendered geometry and fonts, then refined the eyebrow, CTA spacing, card badge size and card padding against the reference. | Chromium loaded Poppins, IvyPresto Display and Font Awesome Free. The 1920px viewport rendered a 584px hero, 318px cards, and 24px gutters. Checks at 1366, 1024, 768 and 390 pixels found no horizontal overflow. |
