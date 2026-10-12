import { cpSync, existsSync, rmSync } from 'node:fs';
const builder = new URL('../../scripts/build-sdk-testing.mjs', import.meta.url);
const classes = new URL('../../scripts/generate-core-classes.mjs', import.meta.url);
const classic = new URL('../../resources/scripts/themes/classic', import.meta.url);
const shippedClassic = new URL('./classic', import.meta.url);
if (existsSync(builder)) await import(builder.href);
else if (!existsSync(new URL('./testing-runtime/testing.js', import.meta.url))) throw new Error('The SDK test runtime is missing. Pack from the panel repository.');
if (existsSync(classes)) await import(classes.href);
else if (!existsSync(new URL('./core-classes.txt', import.meta.url))) throw new Error('The core class manifest is missing. Pack from the panel repository.');
// Classic's source, which `pterodactyl-theme eject` copies from.
if (existsSync(classic)) {
    rmSync(shippedClassic, { recursive: true, force: true });
    cpSync(classic, shippedClassic, { recursive: true, filter: (source) => !/\.spec\.tsx?$/.test(source) });
} else if (!existsSync(shippedClassic)) throw new Error('The classic theme sources are missing. Pack from the panel repository.');
