import { ComponentType, ReactElement } from 'react';
// eslint-disable-next-line no-restricted-imports
import styledImport, { css as cssImport, CSSProp, StyledComponentProps } from 'styled-components';

declare module 'react' {
    interface Attributes {
        css?: CSSProp;
    }
}

declare module 'styled-components' {
    interface StyledComponentBase<
        C extends string | ComponentType<any>,
        // eslint-disable-next-line @typescript-eslint/ban-types
        T extends object,
        // eslint-disable-next-line @typescript-eslint/ban-types
        O extends object = {},
        A extends keyof any = never
    > extends ForwardRefExoticBase<StyledComponentProps<C, T, O, A>> {
        (
            props: StyledComponentProps<C, T, O, A> & { as?: Element | string; forwardedAs?: never | undefined }
        ): ReactElement<StyledComponentProps<C, T, O, A>>;
    }
}

declare module 'twin.macro' {
    const css: typeof cssImport;
    const styled: typeof styledImport;
}
