function isObject<T>(value: T): value is T & object {
    return value !== null && Object(value) === value && !Array.isArray(value) && !(value instanceof Function);
}

function isString<T>(value: T): value is T & string {
    return String(value) === value;
}

function isNumber<T>(value: T): value is T & number {
    return Number.isFinite(value) || Number.isNaN(value) || value === Infinity || value === -Infinity;
}

function isFiniteNumber<T>(value: T): value is T & number {
    return Number.isFinite(value);
}

function isEmptyObject<T extends object>(val: T): boolean {
    return Object.keys(val).length === 0 && Object.getPrototypeOf(val) === Object.prototype;
}

function getObjectKeys<T extends object>(o: T): (keyof T)[] {
    return Object.keys(o) as (keyof typeof o)[];
}

export { isObject, isString, isNumber, isFiniteNumber, isEmptyObject, getObjectKeys };
