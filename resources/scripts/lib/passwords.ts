const passwordCharacterSets = {
    uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    lowercase: 'abcdefghijklmnopqrstuvwxyz',
    numbers: '0123456789',
    symbols: '!@#$%^&*()_+=-[]{}',
} as const;

const secureRandomIndex = (length: number): number => {
    if (length < 1) {
        return 0;
    }

    const limit = 0x100000000 - (0x100000000 % length);
    const values = new Uint32Array(1);
    let value: number;

    do {
        crypto.getRandomValues(values);
        value = values[0];
    } while (value >= limit);

    return value % length;
};

const randomCharacter = (characters: string): string => characters[secureRandomIndex(characters.length)];

/**
 * Generates a secure password using the defined random character sets.
 *
 * @param length The requested password length.
 */
export const generateSecurePassword = (length = 17): string => {
    const characterSets = Object.values(passwordCharacterSets);
    const allCharacters = characterSets.join('');
    const password = characterSets.map(randomCharacter);

    while (password.length < length) {
        password.push(randomCharacter(allCharacters));
    }

    for (let index = password.length - 1; index > 0; index--) {
        const swapIndex = secureRandomIndex(index + 1);
        [password[index], password[swapIndex]] = [password[swapIndex], password[index]];
    }

    return password.join('');
};
