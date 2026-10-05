import React from 'react';
import { Link } from '@tanstack/react-router';

interface Props extends Omit<React.ComponentProps<'a'>, 'href'> {
    to: string;
    params?: Record<string, string>;
    exact?: boolean;
    hash?: string;
}

/** Untyped <Link> for menus whose `to` is built at runtime. */
const NavLink = ({ to, params, exact = false, ...rest }: Props) => (
    <Link to={to as never} params={params as never} activeOptions={{ exact, includeSearch: false }} {...rest} />
);

export default NavLink;
