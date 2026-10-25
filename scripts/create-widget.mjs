import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const slug = String(process.argv[2] || '').trim();
const label = String(process.argv[3] || slug).trim();
if (!/^[a-z][a-z0-9-]{1,48}$/.test(slug)) throw new Error('Slug must use lowercase letters, digits and hyphens.');
const type = slug.replaceAll('-', '_');
const target = resolve('wordpress/plugins/woact-core/widgets', slug);
if (existsSync(target)) throw new Error(`Widget already exists: ${target}`);
mkdirSync(target, { recursive: false });

writeFileSync(resolve(target, 'widget.php'), `<?php
declare(strict_types=1);
if (!defined('ABSPATH') && !defined('WOACT_TESTING')) exit;
return [
    'type' => '${type}',
    'label' => '${label.replaceAll("'", "\\'")}',
    'icon' => 'admin-generic',
    'category' => 'general_widgets',
    'version' => '1.0.0',
    'defaults' => ['title' => '${label.replaceAll("'", "\\'")}'],
    'fields' => [
        ['id' => 'title', 'label' => 'عنوان', 'type' => 'text', 'default' => '${label.replaceAll("'", "\\'")}'],
    ],
    'assets' => ['${slug}'],
    'dependencies' => [],
    'migrations' => [],
];
`);
writeFileSync(resolve(target, 'runtime.js'), `(function(runtime){'use strict';if(!runtime)return;runtime.register('${type}',function(c){return c.h('div',c.common,c.props.title||'');},{version:'1.0.0'});})(window.WoactWidgetRuntime);\n`);
writeFileSync(resolve(target, 'style.css'), `.woact-page-node--${type}{display:block}\n`);
console.log(`Created ${target}`);
