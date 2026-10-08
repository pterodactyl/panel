export const validateShortCode = (value: string): string | undefined => {
    if (value.length < 1) {
        return 'A short code must be provided.';
    }

    if (value.length > 60) {
        return 'A short code must not exceed 60 characters.';
    }

    return undefined;
};
