import { Breakpoints, css, DefaultTheme, StyledProps } from 'styled-components';

declare module 'styled-components' {
    type Breakpoints = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

    export interface DefaultTheme {
        breakpoints: {
            [name in 'xs' | 'sm' | 'md' | 'lg' | 'xl']: number;
        };
    }
}

declare module 'styled-components-breakpoint' {
    type CSSFunction = (...params: Parameters<typeof css>) => <P extends object>({ theme }: StyledProps<P>) => ReturnType<typeof css>;

    export const breakpoint: (breakpointA: Breakpoints, breakpointB?: Breakpoints) => CSSFunction;
}
