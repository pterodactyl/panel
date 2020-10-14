import { BreakpointFunction, createBreakpoint } from 'styled-components-breakpoint';

type Breakpoints = 'xs' | 'sm' | 'md' | 'lg' | 'xl';
export const breakpoint: BreakpointFunction<Breakpoints> = createBreakpoint<Breakpoints>({
    xs: 0,
    sm: 576,
    md: 768,
    lg: 992,
    xl: 1200,
});
