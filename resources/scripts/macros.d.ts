// This allows the use of css={} on JSX elements.
//
// @see https://github.com/DefinitelyTyped/DefinitelyTyped/issues/31245
//
// This is just the contents of the @types/styled-components/cssprop.d.ts file
// since using the other method of just importing the one file did not work
// correctly for some reason.
// noinspection ES6UnusedImports
import {} from 'react';
// eslint-disable-next-line no-restricted-imports
import { CSSProp } from 'styled-components';

declare module 'react' {
    interface Attributes {
        // NOTE: unlike the plain javascript version, it is not possible to get access
        // to the element's own attributes inside function interpolations.
        // Only theme will be accessible, and only with the DefaultTheme due to the global
        // nature of this declaration.
        // If you are writing this inline you already have access to all the attributes anyway,
        // no need for the extra indirection.
        /**
         * If present, this React element will be converted by
         * `babel-plugin-styled-components` into a styled component
         * with the given css as its styles.
         */
        css?: CSSProp;
    }
}
