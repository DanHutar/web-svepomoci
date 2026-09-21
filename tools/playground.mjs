import { runCLI } from '@wp-playground/cli';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

export const projectRoot = fileURLToPath(new URL('../', import.meta.url));

export async function bootWordPress(port = 9400, login = true, sourceRoot = projectRoot) {
  // An upgrade replaces package directories. Mount their parents in disposable
  // test copies, because a mounted directory itself cannot be removed by WASM.
  const packageMounts = sourceRoot === projectRoot ? [
    { hostPath: path.join(sourceRoot, 'theme/ai-web'), vfsPath: '/wordpress/wp-content/themes/ai-web' },
    { hostPath: path.join(sourceRoot, 'plugin/ai-web-studio'), vfsPath: '/wordpress/wp-content/plugins/ai-web-studio' },
  ] : [
    { hostPath: path.join(sourceRoot, 'theme'), vfsPath: '/wordpress/wp-content/themes' },
    { hostPath: path.join(sourceRoot, 'plugin'), vfsPath: '/wordpress/wp-content/plugins' },
  ];
  return runCLI({
    command: 'server', port, login, php: '8.3', wp: '6.8.3', workers: 1,
    'mount-before-install': [
      ...packageMounts,
      { hostPath: path.join(projectRoot, 'examples'), vfsPath: '/aiwp-examples' },
      { hostPath: path.join(projectRoot, 'tests'), vfsPath: '/aiwp-tests' },
    ],
    blueprint: {
      steps: [
        { step: 'activatePlugin', pluginPath: 'ai-web-studio/ai-web-studio.php' },
        { step: 'activateTheme', themeFolderName: 'ai-web' },
        { step: 'setSiteOptions', options: { blogname: 'Ateliér · AI Web', blogdescription: 'Ukázkový web vytvořený vložením kódu', blog_public: '1' } },
      ],
    },
  });
}

export async function phpJson(server, code) {
  const response = await server.playground.run({ code: '<?php require "/wordpress/wp-load.php"; wp_set_current_user(1); ' + code });
  if (response.errors) throw new Error(response.errors);
  try { return JSON.parse(response.text); }
  catch { throw new Error('Unexpected PHP output: ' + response.text); }
}
