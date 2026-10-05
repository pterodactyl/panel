import { defineRule } from '@oxlint/plugins';

/** Generated contracts must carry schema types, including nested properties and arrays. */
export const generatedApiTypesRule = defineRule({
    meta: {
        type: 'problem',
        messages: { unknown: 'Document a concrete OpenAPI schema instead of generating unknown.' },
    },
    create(context) {
        return {
            TSUnknownKeyword(node) {
                const property = node.parent.parent;
                if (
                    property?.type === 'TSPropertySignature' &&
                    property.key.type === 'Literal' &&
                    property.key.value === 202
                )
                    return;
                context.report({ node, messageId: 'unknown' });
            },
        };
    },
});
