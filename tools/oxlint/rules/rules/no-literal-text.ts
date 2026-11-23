import { defineRule, type ESTree } from '@oxlint/plugins';
import { stringLiteralValue } from '../shared/literals.ts';

const TEXT_PROPS = new Set([
    'alt',
    'aria-description',
    'aria-label',
    'confirm',
    'content',
    'description',
    'disabledReason',
    'emptyMessage',
    'emptyState',
    'label',
    'message',
    'placeholder',
    'searchPlaceholder',
    'title',
    'triggerContent',
]);

const hasWords = (value: string): boolean => {
    const text = value.replaceAll(/&#?\w+;/g, ' ');

    return /[A-Za-z]{2}/.test(text) && !/^[^\s]*[./:@_][^\s]*$/.test(text.trim());
};

function literalText(node: ESTree.Node | null | undefined): string | null {
    if (node?.type === 'TemplateLiteral') {
        return node.quasis.map((quasi) => quasi.value.cooked ?? quasi.value.raw).join(' ');
    }

    return stringLiteralValue(node);
}

function isNotifyCall(node: ESTree.CallExpression): boolean {
    const { callee } = node;

    if (callee.type === 'Identifier') {
        return callee.name === 'notifyHttpError' || callee.name === 'toast';
    }

    return callee.type === 'MemberExpression' && callee.object.type === 'Identifier' && callee.object.name === 'toast';
}

export const noLiteralTextRule = defineRule({
    meta: {
        type: 'problem',
        messages: { text: 'Translate interface text with t() so language packs can translate it.' },
    },
    create(context) {
        return {
            JSXText(node) {
                if (hasWords(node.value)) {
                    context.report({ node, messageId: 'text' });
                }
            },
            JSXAttribute(node) {
                if (node.name.type !== 'JSXIdentifier' || !TEXT_PROPS.has(node.name.name)) {
                    return;
                }

                const value = node.value?.type === 'JSXExpressionContainer' ? node.value.expression : node.value;
                const text = value && value.type !== 'JSXEmptyExpression' ? literalText(value) : null;

                if (text !== null && hasWords(text)) {
                    context.report({ node, messageId: 'text' });
                }
            },
            CallExpression(node) {
                if (!isNotifyCall(node)) {
                    return;
                }

                for (const argument of node.arguments) {
                    const text = argument.type === 'SpreadElement' ? null : literalText(argument);

                    if (text !== null && hasWords(text)) {
                        context.report({ node: argument, messageId: 'text' });
                    }
                }
            },
        };
    },
});
