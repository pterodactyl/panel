import React from 'react';
import { NavLink } from 'react-router-dom';
import tw, { styled } from 'twin.macro';

export const SubNavigation = styled.div`
  ${tw`flex flex-row items-center flex-shrink-0 h-12 mb-4 border-b border-neutral-700`};

  & > a {
    ${tw`flex flex-row items-center h-full px-4 border-b text-neutral-300 text-base whitespace-nowrap border-transparent`};

    & > svg {
      ${tw`w-6 h-6 mr-2`};
    }
    
    &:active, &.active {
      ${tw`text-primary-300 border-primary-300`};
    }
  }
`;

interface Props {
    to: string;
    name: string;
    icon: React.ComponentType;
}

export const SubNavigationLink = ({ to, name, icon: IconComponent }: Props) => {
    return (
        <NavLink to={to} exact>
            <IconComponent css={tw`w-6 h-6 mr-2`}/>{name}
        </NavLink>
    );
};
