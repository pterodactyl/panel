import http from '@/api/http';
import * as React from 'react';
import { useState } from 'react';
import * as Icon from 'react-feather';
import tw, { theme } from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import styled from 'styled-components/macro';
import { Link, NavLink } from 'react-router-dom';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import SearchContainer from '@/components/dashboard/search/SearchContainer';

const Navigation = styled.div`
    ${tw`w-full bg-neutral-900 shadow-md overflow-x-auto`};
    
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
    
    & > a, & > button, & > .navigation-link {
        ${tw`flex items-center h-full no-underline text-neutral-300 px-6 cursor-pointer transition-all duration-150`};
        
        &:active, &:hover {
            ${tw`text-neutral-100 bg-black`};
        }
        
        &:active, &:hover, &.active {
            box-shadow: inset 0 -2px ${theme`colors.cyan.700`.toString()};
        }
    }
`;

export default () => {
    const name = useStoreState((state: ApplicationStore) => state.settings.data!.name);
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const [ isLoggingOut, setIsLoggingOut ] = useState(false);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-ignore
            window.location = '/';
        });
    };

    return (
        <Navigation>
            <SpinnerOverlay visible={isLoggingOut} />
            <div css={tw`mx-auto w-full flex items-center`} style={{ maxWidth: '1200px', height: '3.5rem' }}>
                <div id={'logo'}>
                    <Link to={'/'}>
                        {name}
                    </Link>
                </div>
                <RightNavigation>
                    <SearchContainer />
                    <NavLink to={'/'} exact>
                        <Icon.Server />
                    </NavLink>
                    <NavLink to={'/account'}>
                        <Icon.User />
                    </NavLink>
                    <NavLink to={'/store'}>
                        <Icon.ShoppingCart />
                    </NavLink>
                    {rootAdmin &&
                    <a href={'/admin'} rel={'noreferrer'}>
                        <Icon.Settings />
                    </a>
                    }
                    <button onClick={onTriggerLogout}>
                        <Icon.LogOut />
                    </button>
                </RightNavigation>
            </div>
        </Navigation>
    );
};
