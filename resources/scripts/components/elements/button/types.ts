export enum Shape {
    Default,
    IconSquare,
}

export enum Size {
    Default,
    Small,
    Large,
}

export enum Variant {
    Primary,
    Secondary,
}

export const Options = { Shape, Size, Variant };

export type ButtonProps = JSX.IntrinsicElements['button'] & {
    shape?: Shape;
    size?: Size;
    variant?: Variant;
};
