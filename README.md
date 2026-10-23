# Woact

**Woact** is a React-first WordPress theme platform for content, corporate, personal, and WooCommerce websites.

The repository is planned as a monorepo containing:

- an installable WordPress theme;
- a companion core plugin for persistent settings and APIs;
- a shared React component and rendering library;
- a visual header/footer builder;
- a visual page builder;
- general WordPress and WooCommerce widget packs.

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

Planning baseline established. Implementation starts with Stage 1.

