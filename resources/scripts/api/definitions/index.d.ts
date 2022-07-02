import { MarkRequired } from 'ts-essentials';
import { FractalResponseData, FractalResponseList } from '../http';

export type UUID = string;

// eslint-disable-next-line @typescript-eslint/no-empty-interface
export interface Model {}

interface ModelWithRelationships extends Model {
    relationships: Record<string, FractalResponseData | FractalResponseList | undefined>;
}

/**
 * Allows a model to have optional relationships that are marked as being
 * present in a given pathway. This allows different API calls to specify the
 * "completeness" of a response object without having to make every API return
 * the same information, or every piece of logic do explicit null checking.
 *
 * Example:
 *  >> const user: WithLoaded<User, 'servers'> = {};
 *  >> // "user.servers" is no longer potentially undefined.
 */
type WithLoaded<M extends ModelWithRelationships, R extends keyof M['relationships']> = M & {
    relationships: MarkRequired<M['relationships'], R>;
};

/**
 * Helper type that allows you to infer the type of an object by giving
 * it the specific API request function with a return type. For example:
 *
 * type Egg = InferModel<typeof getEgg>;
 */
export type InferModel<T extends (...args: any) => any> = ReturnType<T> extends Promise<infer U> ? U : T;
