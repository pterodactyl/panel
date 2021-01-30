import React from 'react';
import { NavLink } from 'react-router-dom';
import styled from 'styled-components/macro';
import tw from 'twin.macro';

export const SubNavigation = styled.div`
    ${tw`h-12 flex flex-row items-center border-b border-neutral-700 mb-4`};

    & > div {
        ${tw`h-full flex flex-col flex-shrink-0 justify-center`};

        & > a {
            ${tw`h-full flex flex-row items-center text-neutral-300 border-t px-4`};
            border-top-color: transparent !important;

            & > svg {
                ${tw`h-6 w-6 mr-2`};
            }

            & > span {
                ${tw`text-base whitespace-nowrap`};
            }

            &:active, &.active {
                ${tw`text-primary-300 border-b border-primary-300`};
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
