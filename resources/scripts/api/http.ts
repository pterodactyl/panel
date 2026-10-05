import type { AxiosInstance } from 'axios';
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

export function httpErrorToHuman(cause: unknown): string {
    if (axios.isAxiosError(cause) && cause.response?.data) {
        let data: unknown = cause.response.data;

        // Non-JSON responses can still carry a JSON error body.
        if (isString(data)) {
            try {
                data = JSON.parse(data);
            } catch {
                // Not JSON.
            }
        }

        if (isObject(data) && 'errors' in data && Array.isArray(data.errors)) {
            const firstError = data.errors[0];
            if (isObject(firstError) && 'detail' in firstError && isString(firstError.detail)) {
                return firstError.detail;
            }
        }

        // Wings errors, mostly from file uploads.
        if (isObject(data) && 'error' in data && isString(data.error)) {
            return data.error;
        }
    }

    return cause instanceof Error ? cause.message : 'An unexpected error occurred.';
}
