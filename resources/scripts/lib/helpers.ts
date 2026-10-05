function hexToRgba(hex: string, alpha = 1): string {
    // noinspection RegExpSimplifiable
    if (!/#?([a-fA-F0-9]{2}){3}/.test(hex)) {
        return hex;
    }

    // noinspection RegExpSimplifiable
    const pairs = hex.match(/[a-fA-F0-9]{2}/g);
    if (!pairs || pairs.length < 3) {
        return hex;
    }

    const [r, g, b] = pairs.map((v) => parseInt(v, 16));

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export { hexToRgba };
