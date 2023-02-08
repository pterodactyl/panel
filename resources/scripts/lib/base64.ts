function decodeBase64(input: string): string {
    input = input.replace(/-/g, '+').replace(/_/g, '/');

    const pad = input.length % 4;
    if (pad) {
        if (pad === 1) {
            throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
        }

        input += new Array(5 - pad).join('=');
    }

    return input;
}

export { decodeBase64 };
