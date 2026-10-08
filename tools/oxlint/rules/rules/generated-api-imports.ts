import { dirname, resolve, sep } from 'node:path';
import { defineRule } from '@oxlint/plugins';
import type { ESTree } from '@oxlint/plugins';

/** Keep generated transport details behind the panel API wrappers. */
export const generatedApiImportsRule = defineRule({
    meta: {
        type: 'problem',
        messages: { boundary: 'Import the panel API wrapper instead of its generated transport.' },
    },
    create(context) {
        const sourceRoot = resolve(context.cwd, 'resources/scripts');
        const apiRoot = resolve(sourceRoot, 'api');
        const generatedRoot = resolve(apiRoot, 'generated');
        const filename = resolve(context.filename);

        if (filename.startsWith(apiRoot + sep) || filename === resolve(sourceRoot, 'sdk/api.ts')) {
            return {};
        }

        const check = (source: ESTree.Expression) => {
            if (source.type !== 'Literal' || typeof source.value !== 'string') {
                return;
            }

            const imported = source.value;
            const target = imported.startsWith('@/')
                ? resolve(sourceRoot, imported.slice(2))
                : imported.startsWith('.')
                  ? resolve(dirname(filename), imported)
                  : null;

            if (target !== generatedRoot && !target?.startsWith(generatedRoot + sep)) {
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
