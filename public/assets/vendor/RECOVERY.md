# Local preview asset recovery — 2026-10-22

The public vendor assets referenced by `views/layouts/main.php` were missing
from the `codex/yii2-full-stack` checkout at `3230fc8`. The unanchored `vendor/`
ignore rule also matches this directory.

- `arena/css/vendor/bootstrap.min.css`: official Bootstrap 5.3.8, MIT.
  Source: https://raw.githubusercontent.com/twbs/bootstrap/v5.3.8/dist/css/bootstrap.min.css
  SHA256: `d85327d99c7a3ee1f9b5d0500d1370acea3ad2db39c163c2f51f232baedbdede`.
  This exactly matches the entry already recorded in `docs/v2-media-manifest.json`.
- `arena/css/arena-adapted.css`: recovered local layout primitives for existing
  navigation, homepage hero, scoreboard and statistics markup.
- `tabler/components.css` and `tabler/community.css`: recovered local card,
  list, avatar, data-grid and chat primitives.
- `multiverse/gallery.css`: recovered local gallery and native photo-dialog styles.

The four recovered adapters are newly written compatibility styles, not copies
of the teammate's missing adapters. Their old manifest hashes do not apply.
The existing `arena-integration.css`, `matchday.css`, `refinement.css` and
`topic-detail.css` continue to supply the page-specific appearance.

No PHP business logic or sports data was changed by this asset recovery.
