import { mkdir, cp, writeFile } from 'node:fs/promises';
import { build } from 'esbuild';
await build({
  stdin: { contents: "module.exports = require('sanitize-html');", resolveDir: process.cwd() },
  bundle: true, platform: 'node', target: 'node22', format: 'cjs',
  outfile: 'server/generated/sanitize.cjs'
});
await mkdir('dist/assets', { recursive: true });
await cp('assets', 'dist/assets', { recursive: true });
await writeFile('dist/404.html', '<!doctype html><title>Page not found</title><h1>Page not found</h1><a href="/">Return to DeCab</a>');
console.log('Netlify assets prepared. Pages are served by the app function.');
