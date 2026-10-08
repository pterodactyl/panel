import type { AxiosInstance, AxiosResponse } from 'axios';
import axios from 'axios';
import { completeHttpProgress, startHttpProgress } from '@/state/httpProgress';
import { isObject, isString } from '@/lib/objects';

const http: AxiosInstance = axios.create({
    withCredentials: true,
    timeout: 20000,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

const shouldTrackHttpProgress = (url?: string): boolean => !url?.endsWith('/resources');

http.interceptors.request.use((req) => {
    if (req.data instanceof FormData) {
        req.headers.delete('Content-Type');
    }

    if (shouldTrackHttpProgress(req.url)) {
        startHttpProgress();
    }

    return req;
});

http.interceptors.response.use(
    (resp) => {
        if (shouldTrackHttpProgress(resp.config.url)) {
            completeHttpProgress();
        }

        return resp;
    },
    (error) => {
        if (shouldTrackHttpProgress(axios.isAxiosError(error) ? error.config?.url : undefined)) {
            completeHttpProgress();
        }

        throw error;
    }
);

export default http;

function parseJson(text: string): unknown {
    try {
        return JSON.parse(text);
    } catch {
        return text;
    }
}

/** The message in a JSON:API error body, or in a Wings error body (mostly from file uploads). */
function responseErrorMessage(response: AxiosResponse): string | undefined {
    // Non-JSON responses can still carry a JSON error body.
    const data: unknown = isString(response.data) ? parseJson(response.data) : response.data;

    if (!isObject(data)) {
        return undefined;
    }

    if ('errors' in data && Array.isArray(data.errors)) {
        const firstError: unknown = data.errors[0];

        if (isObject(firstError) && 'detail' in firstError && isString(firstError.detail)) {
            return firstError.detail;
        }
    }

    if ('error' in data && isString(data.error)) {
        return data.error;
    }

    return undefined;
}

export function httpErrorToHuman(cause: unknown): string {
    if (axios.isAxiosError(cause) && cause.response?.data) {
        const message = responseErrorMessage(cause.response);

        if (message !== undefined) {
            return message;
        }
    }

    return cause instanceof Error ? cause.message : 'An unexpected error occurred.';
}
