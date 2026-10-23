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
];

const missing = required.filter((path) => !existsSync(path));
if (missing.length) {
  throw new Error(`Stage 2 files missing:\n${missing.join('\n')}`);
}

const theme = readFileSync('wordpress/themes/woact/style.css', 'utf8');
const plugin = readFileSync('wordpress/plugins/woact-core/woact-core.php', 'utf8');
for (const [name, source] of [['theme', theme], ['plugin', plugin]]) {
  if (!source.includes('0.2.0-rc1')) {
    throw new Error(`${name} version is not 0.2.0-rc1`);
  }
}

console.log('Woact Stage 2 structure is valid.');

