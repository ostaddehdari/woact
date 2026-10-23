# Woact

**Woact** is a React-first WordPress theme platform for content, corporate, personal, course, school, and WooCommerce websites.

The repository is planned as a monorepo containing:

- an installable WordPress theme;
- a companion core plugin for persistent settings and APIs;
- a shared React component and rendering library;
- a visual header/footer builder;
- a visual page builder;
- general WordPress and WooCommerce widget packs.

## Stage 1 status

Stage 1 is implemented as version `0.1.0-rc1`:

- installable WordPress theme in `wordpress/themes/woact`;
- companion settings and render-policy plugin in `wordpress/plugins/woact-core`;
- Classic, Hybrid and React render modes;
- automatic Elementor/builder protection;
- per-page migration control;
- React shell powered by WordPress `wp-element`;
- RTL and basic WooCommerce theme support;
- CI validation and ZIP packaging.

The safe default is **Hybrid**: all existing WordPress, Gutenberg, Elementor and plugin-built pages keep their native rendering until a page is explicitly moved to React.

See [docs/STAGE1_FA.md](docs/STAGE1_FA.md) for installation, behavior and acceptance criteria.

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

Stage 1 release candidate implemented. Stage 2 will expand the dashboard settings engine.

