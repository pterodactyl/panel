import React from 'react';
import { NavLink } from 'react-router-dom';
import styled from 'styled-components/macro';
import tw from 'twin.macro';

export const SubNavigation = styled.div`
    ${tw`flex flex-row items-center flex-shrink-0 h-12 mb-4 border-b border-neutral-700`};

    & > div {
        ${tw`flex flex-col justify-center flex-shrink-0 h-full`};

        & > a {
            ${tw`flex flex-row items-center h-full px-4 border-t text-neutral-300`};
            border-top-color: transparent !important;

            & > svg {
                ${tw`w-6 h-6 mr-2`};
            }

            & > span {
                ${tw`text-base whitespace-nowrap`};
            }

            &:active, &.active {
                ${tw`border-b text-primary-300 border-primary-300`};
            }
        }
    }
`;

export const SubNavigationLink = ({ to, name, children }: { to: string, name: string, children: React.ReactNode }) => {
    return (
        <div>
            <NavLink to={to} exact>
                {children}
                <span>{name}</span>
            </NavLink>
        </div>
    );
};
