import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { icons } from 'lucide-react';
import dynamicIconImports from 'lucide-react/dynamicIconImports.mjs';

// The icon names `nav.icon` and the SDK's <NamedIcon> resolve: lucide's canonical names
// (deprecated aliases are left out) in the release the panel is built with. PHP reads
// this list (p:extension:doctor) because it cannot inspect the frontend bundle, and
// extension tooling can read it from the SDK package.
const output = resolve(dirname(fileURLToPath(import.meta.url)), '../packages/sdk/icons.json');
const exportName = (name) => name.replace(/(?:^|-)([a-z0-9])/g, (_match, letter) => letter.toUpperCase());
const names = Object.keys(dynamicIconImports).filter((name) => exportName(name) in icons);
const expected = JSON.stringify(names.sort()) + '\n';

if (process.argv.includes('--check')) {
    if (readFileSync(output, 'utf8') !== expected) {
        console.error('packages/sdk/icons.json is out of date. Run npm run sdk:generate.');
        process.exit(1);
    }
} else {
    writeFileSync(output, expected);
}
