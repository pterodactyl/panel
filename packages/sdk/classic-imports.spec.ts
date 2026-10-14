/** @vitest-environment jsdom */
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';
import * as sdk from '@/sdk';
import { rewriteSdkImports, sdkExportSources } from '../../scripts/classic-sdk-imports.mjs';

const scripts = join(process.cwd(), 'resources/scripts/');
const sources = sdkExportSources(scripts);
const files = (dir: string): string[] =>
    readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);

        return statSync(path).isDirectory() ? files(path) : [path];
    });

describe('classic imports from @/sdk in the build', () => {
    it('come from the module that declares each value, which exports the same binding', async () => {
        for (const [name, value] of Object.entries(sdk)) {
            const declared = sources.resolve(name);

            expect(declared, name).toBeDefined();
            expect(Reflect.get(await import(/* @vite-ignore */ declared.file), declared.name), name).toBe(value);
        }
    });

    it('keep their lines and leave type imports on @/sdk', () => {
        const code = [
            "import { Button, cn as join, type ViewProps } from '@/sdk';",
            'import {',
            '    Badge,',
            "} from '@/sdk';",
            "import { View } from '@/sdk/theme';",
            '',
        ].join('\n');
        const output = rewriteSdkImports(code, 'Example.tsx', sources) ?? '';

        expect(output.split('\n')).toHaveLength(code.split('\n').length);
        expect(output).toContain(`import { default as Button } from ${JSON.stringify(join(scripts, 'components/elements/Button.tsx'))};`);
        expect(output).toContain(`import { cn as join } from ${JSON.stringify(join(scripts, 'sdk/core.ts'))};`);
        expect(output).toContain(`import { default as Badge } from ${JSON.stringify(join(scripts, 'components/elements/Badge.tsx'))};`);
        expect(output).toContain("import type { ViewProps } from '@/sdk';");
        expect(output).toContain("import { View } from '@/sdk/theme';");
        expect(() => rewriteSdkImports("import { Missing } from '@/sdk';", 'Example.tsx', sources)).toThrow(
            '@/sdk does not export Missing'
        );
    });

    it('resolve every import of every classic module', () => {
        for (const file of files(join(scripts, 'themes/classic'))) {
            expect(() => rewriteSdkImports(readFileSync(file, 'utf8'), file, sources), file).not.toThrow();
        }
    });
});
