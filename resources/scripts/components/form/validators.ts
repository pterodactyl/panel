type TextValidator = ({ value }: { value: string }) => string | undefined;

/** A text field validator that requires a value no longer than `max` characters. */
export const requiredWithMaxLength =
    (max: number, missing: string, tooLong: string): TextValidator =>
    ({ value }): string | undefined => {
        if (value.length < 1) {
            return missing;
        }

        if (value.length > max) {
            return tooLong;
        }

        return undefined;
    };
