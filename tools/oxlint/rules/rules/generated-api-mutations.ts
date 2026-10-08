import { defineRule } from '@oxlint/plugins';
import { stringLiteralValue } from '../shared/literals.ts';

/** JSON API mutations use the options emitted by HeyAPI. */
export const generatedApiMutationsRule = defineRule({
    meta: {
        type: 'problem',
        messages: { mutation: 'Use generated mutation options for JSON API requests.' },
    },
    create(context) {
        return {
            Property(node) {
                const { key } = node;
                const name = key.type === 'Identifier' && !node.computed ? key.name : stringLiteralValue(key);

                if (name === 'mutationFn') {
                    context.report({ node: node.key, messageId: 'mutation' });
                }
            },
        };
    },
});
