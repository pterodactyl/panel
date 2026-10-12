import { isValidElement } from 'react';
import { isNumber, isObject, isString } from '@/lib/objects';

export type ModelTone = 'string' | 'number' | 'boolean' | 'empty' | 'function' | 'other';
export type ModelNode =
    | { readonly kind: 'value'; readonly text: string; readonly tone: ModelTone }
    | { readonly kind: 'branch'; readonly summary: string; readonly entries: readonly ModelEntry[] };
export interface ModelEntry {
    readonly key: string;
    readonly node: ModelNode;
}

const MAX_DEPTH = 8;

function elementName<T>(type: T): string {
    if (isString(type)) {
        return type;
    }

    return type instanceof Function && type.name ? type.name : 'Component';
}

const leaf = (text: string, tone: ModelTone): ModelNode => ({ kind: 'value', text, tone });

/** A value printed on one line, or null for an object or array to expand. */
function describeLeaf<T>(value: T): ModelNode | null {
    if (value === null || value === undefined) {
        return leaf(String(value), 'empty');
    }

    if (isString(value)) {
        return leaf(JSON.stringify(value), 'string');
    }

    if (isNumber(value)) {
        return leaf(String(value), 'number');
    }

    if (value === true || value === false) {
        return leaf(String(value), 'boolean');
    }

    if (value instanceof Function) {
        return leaf(`ƒ ${value.name || 'anonymous'}()`, 'function');
    }

    if (value instanceof Date) {
        return leaf(value.toISOString(), 'other');
    }

    if (isValidElement(value)) {
        return leaf(`<${elementName(value.type)} />`, 'other');
    }

    return Array.isArray(value) || isObject(value) ? null : leaf(String(value), 'other');
}

/** A model as a tree of printable values: functions, elements and cycles become labels. */
export function describeModel<T>(value: T, depth = 0, seen: readonly object[] = []): ModelNode {
    const printed = describeLeaf(value);

    if (printed || (!Array.isArray(value) && !isObject(value))) {
        return printed ?? leaf(String(value), 'other');
    }

    if (seen.includes(value)) {
        return leaf('[circular]', 'other');
    }

    if (depth >= MAX_DEPTH) {
        return leaf('…', 'other');
    }

    const pairs: [string, ModelNode][] = Array.isArray(value)
        ? value.map((item, index) => [String(index), describeModel(item, depth + 1, [...seen, value])])
        : Object.entries(value).map(([key, item]) => [key, describeModel(item, depth + 1, [...seen, value])]);

    return {
        kind: 'branch',
        summary: Array.isArray(value) ? `Array(${pairs.length})` : `{${pairs.length}}`,
        entries: pairs.map(([key, node]) => ({ key, node })),
    };
}

/** JSON of a model for the clipboard; functions become their names. */
export function modelJson<T>(value: T): string {
    return JSON.stringify(
        value,
        (_key, item) => (item instanceof Function ? `ƒ ${item.name || 'anonymous'}()` : item),
        2
    );
}
