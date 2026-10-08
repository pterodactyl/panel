import { defineRule } from '@oxlint/plugins';
import type { ESTree } from '@oxlint/plugins';
import { stringLiteralValue } from '../shared/literals.ts';

const palette =
    'slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|white|black';

export const paletteUtility = new RegExp(
    `(?<![\\w-])(?:bg|text|border|divide|ring|outline|shadow|fill|stroke|from|via|to|decoration|accent|caret)-(?:${palette})(?:-(?:50|100|200|300|400|500|600|700|800|900|950))?(?![\\w-])`
);
const colorLiteral = /#[\da-f]{3,8}\b|\b(?:rgba?|hsla?|oklch)\(\s*[\d.]/i;
const arbitraryUtility =
    /\b(?:rounded|shadow|opacity|font|text)-\[[^\]]+\]|\b(?:bg|text|border|ring|outline|fill|stroke)-\[var\(--/;
const paintNames = new RegExp(`^(?:${palette})$`);

/** Check string values in the syntax tree, leaving comments and other source text alone. */
export const designTokensRule = defineRule({
    meta: {
        type: 'problem',
        messages: { token: 'Use a semantic theme token instead of a hardcoded visual value.' },
    },
    create(context) {
        const check = (node: ESTree.Node, value: string) => {
            if (paletteUtility.test(value) || colorLiteral.test(value) || arbitraryUtility.test(value)) {
                context.report({ node, messageId: 'token' });
            }
        };

        return {
            Literal(node) {
                const value = stringLiteralValue(node);

                if (value !== null) {
                    check(node, value);
                }
            },
            TemplateElement: (node) => check(node, node.value.cooked ?? node.value.raw),
            Property(node) {
                if (node.key.type !== 'Identifier' || node.key.name !== 'fontFamily') {
                    return;
                }

                const fontFamily = stringLiteralValue(node.value);

                if (fontFamily === null || fontFamily.startsWith('var(')) {
                    return;
                }

                context.report({ node: node.value, messageId: 'token' });
            },
            JSXAttribute(node) {
                if (
                    node.name.type !== 'JSXIdentifier' ||
                    !['fill', 'stroke', 'stopColor', 'floodColor'].includes(node.name.name)
                ) {
                    return;
                }

                const value = node.value?.type === 'JSXExpressionContainer' ? node.value.expression : node.value;

                const paint = stringLiteralValue(value);

                if (value !== null && value !== undefined && paint !== null && paintNames.test(paint)) {
                    context.report({ node: value, messageId: 'token' });
                }
            },
        };
    },
});
