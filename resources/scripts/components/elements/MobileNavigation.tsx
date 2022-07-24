import http from '@/api/http';
import * as React from 'react';
import { useState } from 'react';
import * as Icon from 'react-feather';
import tw, { theme } from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import styled from 'styled-components/macro';
import { NavLink } from 'react-router-dom';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import SearchContainer from '@/components/dashboard/search/SearchContainer';

const Navigation = styled.div`
    ${tw`w-full bg-neutral-875 shadow-xl overflow-x-auto`};
    & > div {
        ${tw`mx-auto w-full flex items-center`};
    }
    & #logo {
        ${tw`flex-1`};

        & > a {
            ${tw`text-2xl font-header px-4 no-underline text-neutral-200 hover:text-neutral-100 transition-colors duration-150`};
        }
    }
`;

const RightNavigation = styled.div`
    ${tw`flex h-full items-center justify-center`};

    & > a,
    & > button,
    & > .navigation-link {
        ${tw`flex items-center h-full no-underline text-neutral-300 px-4 cursor-pointer transition-all duration-150`};
        &:active,
        &:hover {
            ${tw`text-neutral-100 bg-black`};
        }
        &:active,
        &:hover,
        &.active {
            box-shadow: inset 0 -2px ${theme`colors.cyan.700`.toString()};
        }
    }
`;

export default () => {
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const store = useStoreState((state) => state.storefront.data!);
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    return (
        <Navigation>
            <SpinnerOverlay visible={isLoggingOut} />
            <div
                css={tw`mx-auto w-full flex justify-center items-center`}
                style={{ maxWidth: '1200px', height: '3rem' }}
            >
                <RightNavigation>
                    <SearchContainer size={20} />
                    <NavLink to={'/'} exact>
                        <Icon.Server size={20} />
                    </NavLink>
                    <NavLink to={'/account'}>
                        <Icon.User size={20} />
                    </NavLink>
                    {store.enabled && (
                        <NavLink to={'/store'}>
                            <Icon.ShoppingCart size={20} />
                        </NavLink>
                    )}
                    {rootAdmin && (
                        <a href={'/admin'} rel={'noreferrer'}>
                            <Icon.Settings size={20} />
                        </a>
                    )}
                    <button onClick={onTriggerLogout}>
                        <Icon.LogOut size={20} />
                    </button>
                </RightNavigation>
            </div>
        </Navigation>
    );
};
