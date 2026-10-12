import { dirname, resolve, sep } from 'node:path';
import { defineRule } from '@oxlint/plugins';
import type { ESTree } from '@oxlint/plugins';
import { stringLiteralValue } from '../shared/literals.ts';

const allowed = new Set(['react', 'lucide-react', '@/sdk', '@/sdk/theme']);

/** Classic is built only on the public theme API, so anything it does a theme can do, and eject compiles. */
export const classicThemeImportsRule = defineRule({
    meta: {
        type: 'problem',
        messages: {
            boundary:
                'The classic theme may import only react, lucide-react, @/sdk, @/sdk/theme and files inside classic. Export what it needs from the SDK.',
        },
    },
    create(context) {
        const classicRoot = resolve(context.cwd, 'resources/scripts/themes/classic');
        const filename = resolve(context.filename);

        const check = (source: ESTree.Expression) => {
            const imported = stringLiteralValue(source);

            if (imported === null || allowed.has(imported)) {
                return;
            }

            const target = imported.startsWith('.') ? resolve(dirname(filename), imported) : null;

            if (target?.startsWith(classicRoot + sep)) {
                return;
            }

            context.report({ node: source, messageId: 'boundary' });
        };

        return {
            ImportDeclaration: (node) => check(node.source),
            ExportNamedDeclaration: (node) => {
                if (node.source) {
                    check(node.source);
                }
            },
            ExportAllDeclaration: (node) => check(node.source),
            ImportExpression: (node) => check(node.source),
        };
    },
});
