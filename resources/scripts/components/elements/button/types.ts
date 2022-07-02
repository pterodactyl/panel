enum Shape {
    Default,
    IconSquare,
}

enum Size {
    Default,
    Small,
    Large,
}

enum Variant {
    Primary,
    Success,
    Secondary,
}

export const Options = { Shape, Size, Variant };

export type ButtonProps = JSX.IntrinsicElements['button'] & {
    shape?: Shape;
    size?: Size;
    variant?: Variant;
};
