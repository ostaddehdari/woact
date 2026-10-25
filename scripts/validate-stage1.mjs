import { existsSync, readFileSync } from 'node:fs';

const required = [
  'wordpress/themes/woact/style.css',
  'wordpress/themes/woact/functions.php',
  'wordpress/themes/woact/react-shell.php',
  'wordpress/themes/woact/assets/js/react-app.js',
  'wordpress/plugins/woact-core/woact-core.php',
  'wordpress/plugins/woact-core/includes/class-woact-render-policy.php',
  'wordpress/plugins/woact-core/includes/class-woact-settings.php',
  'wordpress/plugins/woact-core/includes/class-woact-settings-rest-controller.php',
  'wordpress/plugins/woact-core/includes/class-woact-core.php',
  'wordpress/plugins/woact-core/includes/settings-schema.php',
  'wordpress/plugins/woact-core/assets/admin-settings.js',
  'wordpress/plugins/woact-core/assets/admin-settings.css',
  'wordpress/plugins/woact-core/includes/class-woact-layouts.php',
  'wordpress/plugins/woact-core/includes/class-woact-layouts-rest-controller.php',
  'wordpress/plugins/woact-core/assets/admin-layout-builder.js',
  'wordpress/plugins/woact-core/assets/admin-layout-builder.css',
  'wordpress/plugins/woact-core/assets/layout-runtime.js',
  'wordpress/plugins/woact-core/assets/layout-runtime.css',
  'wordpress/plugins/woact-core/examples/woact-ai-header-footer-example.json',
  'wordpress/plugins/woact-core/includes/class-woact-pages.php',
  'wordpress/plugins/woact-core/includes/class-woact-pages-rest-controller.php',
  'wordpress/plugins/woact-core/assets/admin-page-builder.js',
  'wordpress/plugins/woact-core/assets/admin-page-builder.css',
  'wordpress/plugins/woact-core/assets/page-runtime.js',
  'wordpress/plugins/woact-core/assets/page-runtime.css',
  'wordpress/plugins/woact-core/includes/class-woact-widget-registry.php',
  'wordpress/plugins/woact-core/assets/widget-runtime-registry.js',
  'wordpress/plugins/woact-core/widgets/notice-card/widget.php',
  'wordpress/plugins/woact-core/widgets/notice-card/runtime.js',
  'scripts/create-widget.mjs',
  'wordpress/plugins/woact-core/examples/woact-ai-page-example.json',
  'docs/STAGE5_FA.md',
  'docs/WIDGET_SDK_FA.md',
];

const missing = required.filter((path) => !existsSync(path));
if (missing.length) {
  throw new Error(`Stage 5 files missing:\n${missing.join('\n')}`);
}

const theme = readFileSync('wordpress/themes/woact/style.css', 'utf8');
const plugin = readFileSync('wordpress/plugins/woact-core/woact-core.php', 'utf8');
for (const [name, source] of [['theme', theme], ['plugin', plugin]]) {
  if (!source.includes('1.0.3-rc1')) {
    throw new Error(`${name} version is not 1.0.3-rc1`);
  }
}

console.log('Woact Stage 5 structure is valid.');

