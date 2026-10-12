import { readFileSync, statSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { gzipSync } from 'node:zlib';

// Sizes of the main entry and its static import closure (what a first page load fetches before any
// route chunk), read from a build's Vite manifest: node scripts/measure-entry.mjs [assets dir] [--list]
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const args = process.argv.slice(2);
const assets = resolve(args.find((arg) => !arg.startsWith('--')) ?? resolve(root, 'public/assets'));
const manifest = JSON.parse(readFileSync(resolve(assets, 'manifest.json'), 'utf8'));
const entries = {
    main: 'resources/scripts/index.tsx',
    '@pterodactyl/sdk/theme': 'resources/scripts/sdk/theme.ts',
    '@pterodactyl/sdk': 'resources/scripts/sdk/index.ts',
};

function closure(key, seen = new Set()) {
    if (!seen.has(key)) {
        seen.add(key);
        for (const imported of manifest[key]?.imports ?? []) {
            closure(imported, seen);
        }
    }

    return seen;
}

function measure(keys) {
    let raw = 0;
    let gzip = 0;

    for (const key of keys) {
        const contents = readFileSync(resolve(assets, manifest[key].file));
        raw += contents.length;
        gzip += gzipSync(contents).length;
    }

    return `${(raw / 1024).toFixed(1)} KiB raw, ${(gzip / 1024).toFixed(1)} KiB gzip (${keys.size} files)`;
}

const main = closure(entries.main);

for (const [label, key] of Object.entries(entries)) {
    if (!manifest[key]) {
        continue;
    }

    const all = closure(key);
    console.log(`${label}  ${manifest[key].file}`);
    console.log(`  entry chunk:    ${measure(new Set([key]))}`);
    console.log(`  static closure: ${measure(all)}`);

    if (key !== entries.main) {
        console.log(`  beyond main:    ${measure(new Set([...all].filter((file) => !main.has(file))))}`);
    }
}

if (args.includes('--list')) {
    const rows = [...main].map((key) => [statSync(resolve(assets, manifest[key].file)).size, manifest[key].file]);

    for (const [size, file] of rows.sort((a, b) => b[0] - a[0])) {
        console.log(`${(size / 1024).toFixed(1).padStart(8)} KiB  ${file}`);
    }
}
