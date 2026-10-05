import { existsSync } from 'node:fs';
const builder = new URL('../../scripts/build-sdk-testing.mjs', import.meta.url);
if (existsSync(builder)) await import(builder.href);
else if (!existsSync(new URL('./testing-runtime/testing.js', import.meta.url))) throw new Error('The SDK test runtime is missing. Pack from the panel repository.');
