import { isNumber, isObject, isString } from '@/lib/objects';

type YamlScalar = string | number | boolean | null;

export type YamlValue = YamlScalar | readonly YamlValue[] | YamlMapping;

export interface YamlMapping {
    readonly [key: string]: YamlValue;
}

type YamlCollection = readonly YamlValue[] | YamlMapping;

const indentUnit = '  ';
const plainKey = /^[A-Za-z_][\w-]*$/;
const reservedPlainKey = /^(?:true|false|yes|no|on|off|y|n|null)$/i;

const isSequence = (value: YamlValue): value is readonly YamlValue[] => Array.isArray(value);

const isCollection = (value: YamlValue): value is YamlCollection => isSequence(value) || isObject(value);

const isEmptyCollection = (value: YamlCollection): boolean =>
    isSequence(value) ? value.length === 0 : Object.keys(value).length === 0;

// JSON-quoted strings are valid YAML double-quoted scalars.
const renderKey = (key: string): string =>
    plainKey.test(key) && !reservedPlainKey.test(key) ? key : JSON.stringify(key);

const renderScalar = (value: YamlScalar): string => {
    if (value === null) {
        return 'null';
    }

    if (isString(value)) {
        return JSON.stringify(value);
    }

    if (isNumber(value) && Number.isNaN(value)) {
        return '.nan';
    }

    if (isNumber(value) && !Number.isFinite(value)) {
        return value > 0 ? '.inf' : '-.inf';
    }

    return String(value);
};

const renderInline = (value: YamlValue): string => {
    if (!isCollection(value)) {
        return renderScalar(value);
    }

    return isSequence(value) ? '[]' : '{}';
};

const renderBlock = (value: YamlCollection, depth: number): string[] => {
    const pad = indentUnit.repeat(depth);
    const lines: string[] = [];

    if (isSequence(value)) {
        for (const item of value) {
            if (!isCollection(item) || isEmptyCollection(item)) {
                lines.push(`${pad}- ${renderInline(item)}`);
                continue;
            }

            const nested = renderBlock(item, depth + 1);

            nested[0] = `${pad}- ${(nested[0] ?? '').trimStart()}`;
            lines.push(...nested);
        }

        return lines;
    }

    for (const [key, item] of Object.entries(value)) {
        const prefix = `${pad}${renderKey(key)}:`;

        if (!isCollection(item) || isEmptyCollection(item)) {
            lines.push(`${prefix} ${renderInline(item)}`);
        } else {
            lines.push(prefix, ...renderBlock(item, depth + 1));
        }
    }

    return lines;
};

export const toYaml = (value: YamlValue): string =>
    isCollection(value) && !isEmptyCollection(value) ? renderBlock(value, 0).join('\n') : renderInline(value);
