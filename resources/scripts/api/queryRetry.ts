import { isAxiosError } from 'axios';

export const isClientErrorResponse = (error: Error): boolean => {
    const status = isAxiosError(error) ? error.response?.status : undefined;

    return status !== undefined && status >= 400 && status < 500;
};

export const retryUnlessClientError =
    (maxRetries = 1) =>
    (failureCount: number, error: Error): boolean =>
        failureCount < maxRetries && !isClientErrorResponse(error);
