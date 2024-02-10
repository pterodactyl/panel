import type { BreakpointFunction } from 'styled-components-breakpoint';
import { createBreakpoint } from 'styled-components-breakpoint';

type Breakpoints = 'xs' | 'sm' | 'md' | 'lg' | 'xl';
export const breakpoint: BreakpointFunction<Breakpoints> = createBreakpoint<Breakpoints>({
    xs: 0,
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
});
