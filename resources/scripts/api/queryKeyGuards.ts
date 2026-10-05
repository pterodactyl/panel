import type { QueryKey } from '@tanstack/react-query';
import { isObject, isString } from '@/lib/objects';

interface GeneratedQueryKeyRoot {
    _id?: string;
}

const isGeneratedQueryKeyRoot = (value: QueryKey[number]): value is GeneratedQueryKeyRoot =>
    isObject(value) && (!('_id' in value) || value._id === undefined || isString(value._id));

export const hasGeneratedOperationId = (queryKey: QueryKey, id: string): boolean => {
    const [key] = queryKey;

    return isGeneratedQueryKeyRoot(key) && key._id === id;
};

export const hasPathParam = (queryKey: QueryKey, name: string, value: string): boolean => {
    const [key] = queryKey;

    return isObject(key) && 'path' in key && isObject(key.path) && Reflect.get(key.path, name) === value;
};
