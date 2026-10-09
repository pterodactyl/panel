export const validateMountName = (value: string): string | undefined => {
    if (value.length < 2) {
        return 'A name must be at least 2 characters.';
    }

    if (value.length > 64) {
        return 'A name must not exceed 64 characters.';
    }

    return undefined;
};
