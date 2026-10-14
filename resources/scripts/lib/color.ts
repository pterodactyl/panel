let canvas: CanvasRenderingContext2D | null | undefined;

const hex = (value: number) => value.toString(16).padStart(2, '0');

/**
 * Any colour the browser understands, such as `oklch()`, `color-mix()` or a name, as `#rrggbb`, or
 * `rgba()` when translucent: the forms canvas engines like the terminal's parse. The browser paints
 * it on a pixel and reads it back. Without a canvas, the colour is returned as it is.
 */
export function toRgbColor(color: string): string {
    canvas ??= document.createElement('canvas').getContext('2d', { willReadFrequently: true });

    if (!canvas) {
        return color;
    }

    // An invalid colour leaves fillStyle as it was, so it paints as transparent.
    canvas.clearRect(0, 0, 1, 1);
    canvas.fillStyle = 'transparent';
    canvas.fillStyle = color;
    canvas.fillRect(0, 0, 1, 1);

    const [red, green, blue, alpha] = canvas.getImageData(0, 0, 1, 1).data;

    return alpha === 255
        ? `#${hex(red)}${hex(green)}${hex(blue)}`
        : `rgba(${red}, ${green}, ${blue}, ${Math.round((alpha / 255) * 1000) / 1000})`;
}
