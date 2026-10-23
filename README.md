# Woact

**Woact** is a React-first WordPress theme platform for content, corporate, personal, course, school, and WooCommerce websites.

The repository is planned as a monorepo containing:

- an installable WordPress theme;
- a companion core plugin for persistent settings and APIs;
- a shared React component and rendering library;
- a visual header/footer builder;
- a visual page builder;
- general WordPress and WooCommerce widget packs.

## Stage 3 status

Stages 1–3 are implemented as version `0.3.0-rc1`:

- installable WordPress theme in `wordpress/themes/woact`;
- companion settings, API and render-policy plugin in `wordpress/plugins/woact-core`;
- Classic, Hybrid and React render modes;
- automatic Elementor/builder protection;
- per-page migration control;
- React shell powered by WordPress `wp-element`;
- RTL and basic WooCommerce theme support;
- versioned settings schema with 171 validated settings in 21 groups;
- React dashboard with search, conditional fields and responsive RTL UI;
- draft autosave, revisions, restore, presets, import/export and grouped reset;
- settings for layout, notices, typography, blog, portfolio, WooCommerce, account, social, integrations, performance, maintenance and custom code;
- CI validation and ZIP packaging.
- visual React header/footer builder with drag and drop;
- versioned header/footer JSON documents with separate draft and published states;
- multiple templates, display conditions, priorities, duplicate, import/export and revision restore;
- desktop/tablet/mobile visibility, row and column controls, sticky/overlay/shrink behavior;
- shared React layout renderer for both React routes and classic/Elementor routes;
- WordPress navigation, user and WooCommerce cart data resolvers.

The safe default is **Hybrid**: all existing WordPress, Gutenberg, Elementor and plugin-built pages keep their native rendering until a page is explicitly moved to React.

See [docs/STAGE1_FA.md](docs/STAGE1_FA.md) for installation, behavior and acceptance criteria.
See [docs/STAGE2_FA.md](docs/STAGE2_FA.md) for the settings engine and upgrade guide.
See [docs/STAGE3_FA.md](docs/STAGE3_FA.md) for the header/footer builder and publishing workflow.

## Roadmap

The implementation is divided into five stages and fifty work packages. See [docs/ROADMAP_FA.md](docs/ROADMAP_FA.md).

## Architectural principles

- React is the canonical UI runtime for Woact pages and components.
- WordPress remains the CMS, authentication, permissions, media, routing, revision, and plugin host.
- Page and builder documents use versioned JSON schemas.
- Feature data lives in plugins; the theme owns presentation and rendering.
- RTL/LTR, responsive behavior, accessibility, SEO, performance, and WooCommerce compatibility are first-class requirements.
- Release artifacts must be installable ZIP packages and must not require Node.js in production.

## Status

Stage 3 release candidate implemented. Stage 4 will add the visual React page builder.

