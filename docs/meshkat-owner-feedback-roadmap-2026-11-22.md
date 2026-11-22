# Meshkat — roadmap from owner feedback (2026-11-22)

> Status: **requirements extracted; partial implementation only**. Do **not** deploy these branches to production: deployed plugin versions differ from the checked-in sources.
>
> Reported production: Meshkat Woact Demo 1.9.1; MySchool 3.3.1; Studiare Learning Suite 1.3.0; Woact Core 1.6.1-rc1.
> Accessible source: \`ostaddehdari/woact\` contains Woact Core 1.1.1-rc1 + fallback Woact theme; \`ostaddehdari/myschool\` contains MySchool 0.1.8. Neither Studiare 1.3.0 nor Meshkat Demo 1.9.1 source was found in accessible repositories. Production source parity is a **blocking prerequisite** for release.

## Definition of done
Every work item requires: source implementation for the *installed* version, no placeholder datasets, server-side authorization, keyboard/mobile accessibility, Persian labels, backward-compatible upgrade path, automated checks where available, and a staging test of original affected routes. Preserve all existing user/order/class data.

## S01 — Header, mobile, links (Meshkat Demo + Woact theme/core)
- W01.01 Remove top announcement bar, including any duplicated widget-level bar.
- W01.02 Responsive header: logo, hamburger without 'منوی مشکات', user sign-in icon on guest mobile.
- W01.03 Authenticated header: working search/cart controls, logout inside menu; verify logout nonce.
- W01.04 Fix search icon truncation and horizontal overflow at 320, 375, 768, 1024px.
- W01.05 Connect top 'پنل/شعبه‌ها/تماس' controls to real routes; do not render dead links.
- W01.06 Repair homepage links for free classes, courses, events, and articles against published permalinks.
- W01.07 Buffet section picture: realistic food in a pot + apple; retain media alt text.
**Acceptance:** all navigation works as guest and logged-in user on desktop/mobile; no horizontal scrolling; dynamic Woact header override tested (not just fallback template).

## S02 — Real data and taxonomy (Studiare + MySchool)
- W02.01 Each filter option must be generated from published content/related entities, and each selected value must filter the actual query.
- W02.02 Branches: create/edit/disable in MySchool, reflect published/available branches immediately in filters, links, and header.
- W02.03 Age bands: CRUD in settings with unique labels, valid min/max, and stable IDs (not hardcoded age texts).
- W02.04 Automatically expose newly added active age groups in all applicable catalogs; associate courses/events/free classes explicitly.
- W02.05 Real distinct taxonomies for course, event, and free-class categories; decide compatibility/migration with Woo categories and Woact widgets.
- W02.06 Filter combinations, counts, pagination, loading/empty state, and reset query params all work; no hardcoded demo values.
**Acceptance:** create branch and age band in admin → they become selectable without code edits; filtering returns only eligible items.

## S03 — Free class journey (Studiare + Meshkat Demo)
- W03.01 Attractive free-class detail view with cover, instructor, level, date, sessions, venue, age, price and CTA.
- W03.02 Capacity bar = confirmed registrations / capacity (never raw Woo stock without mapping), display remaining seats.
- W03.03 Persian calendar view for first/last session + all scheduled sessions; timezone aware.
- W03.04 Optional micro-interaction before signup: bookshelf displaying *licensed/original* nostalgic book-cover illustrations evoking the 1360s–1370s (no unauthorized copyrighted covers).
- W03.05 Selecting any book advances to the same signup, remembers selection only if necessary, and offers obvious 'رد کردن' for accessibility.
- W03.06 Replace 'جزئیات محصول' with 'جزئیات کلاس' only for free classes.
- W03.07 Reserve seats atomically on confirmed enrollment, release cancelled/expired holds, prohibit oversubscription even for simultaneous checkouts.
**Acceptance:** one student cannot book unavailable class; two simultaneous final-seat registrations cannot both succeed; calendar and progress reflect real records.

## S04 — Detail pages, commerce, error fixes (Studiare + WooCommerce)
- W04.01 Display 'ظرفیت' instead of 'انبار' on course, event and lesson products; physical buffet stock remains 'موجودی'.
- W04.02 Add configurable technical/specification schema per product kind; not a hardcoded feature list.
- W04.03 Improve tabs for introduction, instructor, session details, terms, support, FAQs, reviews.
- W04.04 Reproduce and fix \`wp-comment\` login/comment submission error; validate login redirect, nonce, Ajax and WP comment status.
- W04.05 Hide the WooCommerce \`downloads\` menu entry in user accounts, **without deleting entitlements or files**.
- W04.06 Verify rendered pages in classic HTML and React modes, Woact widget compatibility.
**Acceptance:** stock never mislabeled for buffet; review submit does not show JS/PHP console error; specs are editable through admin.

## S05 — Parent dashboard (MySchool)
- W05.01 Every attendance item must identify student name/class/date and provide student selector; enforce parent→child relation in backend.
- W05.02 Badge for new homework on parent dashboard/menu; unread is tracked per parent per child, not global count.
- W05.03 Extend unread badges to announcements/messages/other actionable inbox items without hiding old entries.
- W05.04 Monthly feedback from parents about relevant teachers, staff and school services (not all staff across unrelated branches).
- W05.05 Role-restricted aggregate survey reports; consent/privacy, duplicate-response prevention and monthly period.
- W05.06 Remove duplicate 'افزودن ولی جدید' button in \`admin.php?page=myschool-guardians\`.
**Acceptance:** parent of two children never sees unattributed attendance; badges clear only on appropriate read/action; monthly survey works per school/month.

## S06 — Organization and permissions (MySchool)
- W06.01 Persian label catalog for each capability and all base/custom position names; internal keys stay stable.
- W06.02 CRUD for organizational posts with explicit editable capabilities and server validation.
- W06.03 Integrate positions with teachers/staff identities and dashboard role resolution.
- W06.04 Show editable permissions matrix and descriptions, not opaque English role slugs.
- W06.05 Multi-position users retain base role; effective capability union and school/branch scoping audited.
- W06.06 Permission changes logged; prevent assigning administrative privileges through posts or tampering with other schools.
**Acceptance:** manager can explain every available permission in Persian; a new post becomes assignable to a real staff member and changes authorized pages only.

## S07 — Auth, migration and quality gates (All four)
- W07.01 Public frontend login route for guest and non-admin; no user-facing \`wp-login.php\` or admin panel destination unless safe fallback configured.
- W07.02 Role-aware post-login redirection: parents, staff, teachers to proper frontend dashboard.
- W07.03 Administrator to WP admin only; ensure admins can still recover/login if custom login fails.
- W07.04 Never block \`admin-ajax.php\`, \`admin-post.php\`, REST, cron or legitimate Woo checkout callbacks; enforce capabilities on server, not nav alone.
- W07.05 Regression matrix: two children, multi-role, two branches, mobile viewports, concurrent last seat, guest/purchased product, Persian/Jalali dates.
- W07.06 Version alignment, database backup, staged rollout, rollback documentation and tests.

## Actual code scope started
- \`woact\` branch \`feature/s01-mobile-header-20261122\`: responsive **fallback theme** header, functional guest login icon / authenticated search and cart and logout navigation, hides Woo downloads menu, CSS. **Custom Woact layout + Meshkat Demo header still unmodified.**
- \`myschool\` branch \`feature/s06-persian-permissions-20261122\`: Persian capability catalog and new custom post editor/assignment module. **Not production MySchool 3.3.1**; no actual teacher/staff entity or branch scope available to integrate against.

## Required next implementation inputs (blocking for parity, not for this roadmap)
1. Exact ZIP/source tag for all four installed plugin versions (with generated assets/build scripts if applicable), or private repository links.
2. Sanitized sample data/schema for school branches, age groups, child-parent relation, enrollments/lesson dates, staff posts, comments and unread notifications.
3. Staging URL and read-only test credentials for three roles and a test order; never modify live school/order records until acceptance.
