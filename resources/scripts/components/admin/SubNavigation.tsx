import type { ComponentType, ReactNode } from 'react';
import { NavLink } from 'react-router-dom';
import tw, { styled } from 'twin.macro';

export const SubNavigation = styled.div`
    ${tw`flex flex-row items-center flex-shrink-0 h-12 mb-4 border-b border-neutral-700`};

    & > a {
        ${tw`flex flex-row items-center h-full px-4 border-b text-neutral-300 text-base whitespace-nowrap border-transparent`};

        & > svg {
            ${tw`w-6 h-6 mr-2`};
        }

        &:active,
        &.active {
            ${tw`text-primary-300 border-primary-300`};
        }
    }
`;

interface Props {
    to: string;
    name: string;
}

interface PropsWithIcon extends Props {
    icon: ComponentType;
    children?: never;
}

interface PropsWithoutIcon extends Props {
    icon?: never;
    children: ReactNode;
}

export const SubNavigationLink = ({ to, name, icon: IconComponent, children }: PropsWithIcon | PropsWithoutIcon) => (
    <NavLink to={to} end>
        {IconComponent ? <IconComponent /> : children}
        {name}
    </NavLink>
);
