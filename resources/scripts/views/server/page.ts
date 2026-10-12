/** A server page that shows `error`, with a retry, in place of its content when that failed to load. */
export interface ServerPageModel {
    readonly error: string | null;
    readonly retry: () => void;
}

const noop = () => {};

export const serverPageFixtures = {
    default: (): ServerPageModel => ({ error: null, retry: noop }),
    error: (): ServerPageModel => ({ error: 'Request failed with status code 500.', retry: noop }),
};
