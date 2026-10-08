import { defineRule } from '@oxlint/plugins';

/** JSON API mutations use the options emitted by HeyAPI. */
export const generatedApiMutationsRule = defineRule({
    meta: {
        type: 'problem',
        messages: { mutation: 'Use generated mutation options for JSON API requests.' },
    },
    create(context) {
        return {
            Property(node) {
                const name =
                    node.key.type === 'Identifier' && !node.computed
                        ? node.key.name
                        : node.key.type === 'Literal'
                          ? node.key.value
                          : null;

                if (name === 'mutationFn') {
                    context.report({ node: node.key, messageId: 'mutation' });
                }
            },
        };
    },
});
