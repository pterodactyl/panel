import type { ESTree } from '@oxlint/plugins';

/**
 * ESTree literal nodes share one shape and are discriminated only by the runtime type of `value`,
 * so this is the single place lint rules decode a node into its string value.
 */
export function stringLiteralValue(node: ESTree.Node | null | undefined): string | null {
    return node?.type === 'Literal' && typeof node.value === 'string' ? node.value : null;
}
