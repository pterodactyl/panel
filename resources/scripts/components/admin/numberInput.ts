/** `null` while the input is empty. */
export type NumberInputValue = number | null;

export const requiredNumber =
    (message: string, check?: (value: number) => string | undefined) =>
    ({ value }: { value: NumberInputValue }): string | undefined =>
        value === null ? message : check?.(value);

export const submittedNumber = (value: NumberInputValue): number => {
    if (value === null) {
        throw new Error('A required number input was submitted while empty.');
    }

    return value;
};
