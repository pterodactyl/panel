import { defineRule, type ESTree } from '@oxlint/plugins';
import { stringLiteralValue } from '../shared/literals.ts';

function isLiteralKey(node: ESTree.Node | undefined): boolean {
    if (node?.type === 'TemplateLiteral') {
        return node.expressions.length === 0;
    }

    return stringLiteralValue(node) !== null;
}

function isTranslateCallee(node: ESTree.Node): boolean {
    if (node.type === 'Identifier') {
        return node.name === 't';
    }

    return (
        node.type === 'MemberExpression' &&
        !node.computed &&
        node.property.type === 'Identifier' &&
        node.property.name === 't'
    );
}

export const literalTranslationKeysRule = defineRule({
    meta: {
        type: 'problem',
        messages: { literal: 'Pass the English text to t() as a string literal so lang:extract can find it.' },
    },
    create(context) {
        return {
            CallExpression(node) {
                const [key] = node.arguments;

                if (
                    isTranslateCallee(node.callee) &&
                    key !== undefined &&
                    key.type !== 'SpreadElement' &&
                    !isLiteralKey(key)
                ) {
                    context.report({ node: key, messageId: 'literal' });
                }
            },
        };
    },
});
