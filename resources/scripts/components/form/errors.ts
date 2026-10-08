import { isObject, isString } from '@/lib/objects';

// Field validators produce strings; standard-schema adapters produce `{ message }` objects.
export function firstError<T>(errors: T[]): string | undefined {
    if (!errors || errors.length === 0) {
        return undefined;
    }

    const error = errors.find((e) => e !== undefined && e !== null && e !== '');

    if (error === undefined) {
        return undefined;
    }

    if (isString(error)) {
        return error;
    }

    if (isObject(error) && 'message' in error) {
        return String(error.message);
    }

    return String(error);
}
