/**
 * Determines if the value provided to the function is an object type that
 * is not null.
 */
function isObject(val: unknown): val is Record<string, unknown> {
    return typeof val === 'object' && val !== null && !Array.isArray(val);
}

/**
 * Determines if an object is truly empty by looking at the keys present
 * and the prototype value.
 */
// eslint-disable-next-line @typescript-eslint/ban-types
function isEmptyObject(val: {}): boolean {
    return Object.keys(val).length === 0 && Object.getPrototypeOf(val) === Object.prototype;
}

/**
 * A helper function for use in TypeScript that returns all of the keys
 * for an object, but in a typed manner to make working with them a little
 * easier.
 */
// eslint-disable-next-line @typescript-eslint/ban-types
function getObjectKeys<T extends {}>(o: T): (keyof T)[] {
    return Object.keys(o) as (keyof typeof o)[];
}

export { isObject, isEmptyObject, getObjectKeys };
