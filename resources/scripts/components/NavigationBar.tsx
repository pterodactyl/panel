import * as React from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCogs, faLayerGroup, faSignOutAlt, faUserCircle } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SearchContainer from '@/components/dashboard/search/SearchContainer';
import tw, { theme } from 'twin.macro';
import styled from 'styled-components/macro';

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
    
    & > a, & > .navigation-link {
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

    return (
        <Navigation>
            <div css={tw`mx-auto w-full flex items-center`} style={{ maxWidth: '1200px', height: '3.5rem' }}>
                <div id={'logo'}>
                    <Link to={'/'}>
                        {name}
                    </Link>
                </div>
                <RightNavigation>
                    <SearchContainer/>
                    <NavLink to={'/'} exact>
                        <FontAwesomeIcon icon={faLayerGroup}/>
                    </NavLink>
                    <NavLink to={'/account'}>
                        <FontAwesomeIcon icon={faUserCircle}/>
                    </NavLink>
                    {rootAdmin &&
                    <a href={'/admin'} rel={'noreferrer'}>
                        <FontAwesomeIcon icon={faCogs}/>
                    </a>
                    }
                    <a href={'/auth/logout'}>
                        <FontAwesomeIcon icon={faSignOutAlt}/>
                    </a>
                </RightNavigation>
            </div>
        </Navigation>
    );
};
