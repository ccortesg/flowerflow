import fs from 'fs/promises';
import path from 'path';
import process from 'process';
import { getIconsCSS } from '@iconify/utils';

const projectRoot = path.resolve(import.meta.dirname, '..');
const resourcesRoot = path.join(projectRoot, 'resources');
const outputPath = path.join(resourcesRoot, 'assets/vendor/fonts/iconify/iconify.css');
const iconSetPath = path.join(projectRoot, 'node_modules/@iconify/json/json/ri.json');
const sourceExtensions = new Set(['.blade.php', '.css', '.js', '.php']);

async function sourceFiles(directory) {
  const files = [];
  for (const entry of await fs.readdir(directory, { withFileTypes: true })) {
    const fullPath = path.join(directory, entry.name);
    const relativePath = path.relative(resourcesRoot, fullPath);

    if (entry.isDirectory()) {
      if (relativePath === 'assets' || relativePath.startsWith(`assets${path.sep}`)) continue;
      files.push(...await sourceFiles(fullPath));
      continue;
    }

    if ([...sourceExtensions].some(extension => entry.name.endsWith(extension))) {
      files.push(fullPath);
    }
  }

  return files;
}

const icons = new Set();
for (const file of await sourceFiles(resourcesRoot)) {
  const source = await fs.readFile(file, 'utf8');
  for (const match of source.matchAll(/\bri-([a-z0-9-]+)\b/g)) {
    icons.add(match[1]);
  }
}

const iconSet = JSON.parse(await fs.readFile(iconSetPath, 'utf8'));
const names = [...icons].sort();
const missing = names.filter(name => !iconSet.icons[name] && !iconSet.aliases?.[name]);
if (missing.length > 0) {
  throw new Error(`Iconos Remix inexistentes: ${missing.join(', ')}`);
}

const css = [
  '/* Archivo generado por scripts/generate_icon_css.mjs. No editar manualmente. */',
  getIconsCSS(iconSet, names, {
    iconSelector: '.{prefix}-{name}',
    commonSelector: '.ri',
    format: 'expanded'
  }).trim(),
  ''
].join('\n');

if (process.argv.includes('--write')) {
  await fs.writeFile(outputPath, css, 'utf8');
  process.stdout.write(`CSS de ${names.length} iconos actualizado.\n`);
} else if (process.argv.includes('--check')) {
  const current = await fs.readFile(outputPath, 'utf8');
  if (current !== css) {
    process.stderr.write('El CSS de iconos no coincide. Ejecuta: corepack yarn icons:write\n');
    process.exitCode = 1;
  } else {
    process.stdout.write(`CSS de ${names.length} iconos verificado.\n`);
  }
} else {
  process.stderr.write('Usa --check o --write.\n');
  process.exitCode = 2;
}
