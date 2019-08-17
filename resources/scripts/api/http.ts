import axios, { AxiosInstance } from 'axios';

const http: AxiosInstance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': (window as any).X_CSRF_TOKEN as string || '',
    },
});

// If we have a phpdebugbar instance registered at this point in time go
// ahead and route the response data through to it so things show up.
// @ts-ignore
if (typeof window.phpdebugbar !== 'undefined') {
    http.interceptors.response.use(response => {
        // @ts-ignore
        window.phpdebugbar.ajaxHandler.handle(response.request);

        return response;
    });
}

export default http;

/**
 * Converts an error into a human readable response. Mostly just a generic helper to
 * make sure we display the message from the server back to the user if we can.
 */
export function httpErrorToHuman (error: any): string {
    if (error.response && error.response.data) {
        const { data } = error.response;
        if (data.errors && data.errors[0] && data.errors[0].detail) {
            return data.errors[0].detail;
        }
    }

    return error.message;
}

export interface PaginatedResult<T> {
    items: T[];
    pagination: PaginationDataSet;
}

interface PaginationDataSet {
    total: number;
    count: number;
    perPage: number;
    currentPage: number;
    totalPages: number;
}

export function getPaginationSet (data: any): PaginationDataSet {
    return {
        total: data.total,
        count: data.count,
        perPage: data.per_page,
        currentPage: data.current_page,
        totalPages: data.total_pages,
    };
}
