import { definePlugin } from '@oxlint/plugins';

import { noChainedTypeAssertionsRule } from './rules/no-chained-type-assertions.ts';
import { noConditionalEmptyObjectSpreadRule } from './rules/no-conditional-empty-object-spread.ts';
import { noKnownValueWideningRule } from './rules/no-known-value-widening.ts';
import { noObjectParametersRule } from './rules/no-object-parameters.ts';
import { noRuntimeTypeofRule } from './rules/no-runtime-typeof.ts';
import { noForbiddenTermInSymbolNamesRule } from './rules/no-shape-in-symbol-names.ts';
import { noUnknownParametersRule } from './rules/no-unknown-parameters.ts';
import { noUnknownTypeAliasesRule } from './rules/no-unknown-type-aliases.ts';
import { noUnsafeDictionaryTypeRule } from './rules/no-unsafe-dictionary-type.ts';
import { noWidenThenAssertRule } from './rules/no-widen-then-assert.ts';
import { generatedApiImportsRule } from './rules/generated-api-imports.ts';
import { generatedApiTypesRule } from './rules/generated-api-types.ts';
import { generatedApiMutationsRule } from './rules/generated-api-mutations.ts';
import { designTokensRule } from './rules/design-tokens.ts';

/** Generic Oxlint rules that reject low-evidence and low-signal implementation patterns. */
const rulesPlugin = definePlugin({
    meta: { name: 'rules' },
    rules: {
        'generated-api-imports': generatedApiImportsRule,
        'generated-api-types': generatedApiTypesRule,
        'generated-api-mutations': generatedApiMutationsRule,
        'design-tokens': designTokensRule,
        'no-chained-type-assertions': noChainedTypeAssertionsRule,
        'no-conditional-empty-object-spread': noConditionalEmptyObjectSpreadRule,
        'no-known-value-widening': noKnownValueWideningRule,
        'no-object-parameters': noObjectParametersRule,
        'no-runtime-typeof': noRuntimeTypeofRule,
        'no-unsafe-dictionary-type': noUnsafeDictionaryTypeRule,
        'no-shape-in-symbol-names': noForbiddenTermInSymbolNamesRule,
        'no-unknown-parameters': noUnknownParametersRule,
        'no-unknown-type-aliases': noUnknownTypeAliasesRule,
        'no-widen-then-assert': noWidenThenAssertRule,
    },
});

export default rulesPlugin;
