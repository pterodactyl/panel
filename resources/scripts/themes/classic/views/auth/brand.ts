import { createContext } from 'react';
import type { ViewProps } from '@/sdk/theme';

/** The brand of `layout.auth`, which the sign-in card shows; null for a page rendered outside it. */
export const AuthBrandContext = createContext<ViewProps<'layout.auth'>['model']['brand'] | null>(null);
