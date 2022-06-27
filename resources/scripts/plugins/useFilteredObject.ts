/**
 * Similar to "withQueryBuilderParams" except this function filters out any null,
 * undefined, or empty string key values. This allows the parameters to be used for
 * caching without having to account for all of the different data combinations.
 */
import { isEmptyObject, isObject } from '@/lib/objects';

// eslint-disable-next-line @typescript-eslint/ban-types
export default <T extends {}>(data: T): T => {
    const empty = [undefined, null, ''] as unknown[];

    const removeEmptyValues = (input: T): T =>
        Object.entries(input)
            .filter(([_, value]) => !empty.includes(value))
            .reduce((obj, [k, v]) => {
                const parsed = isObject(v) ? removeEmptyValues(v as any) : v;

                return isObject(parsed) && isEmptyObject(parsed) ? obj : { ...obj, [k]: parsed };
            }, {} as T);

    return removeEmptyValues(data);
};
