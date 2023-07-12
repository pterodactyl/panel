import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faLayerGroup, faScrewdriverWrench, faSignOutAlt } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import SearchContainer from '@/components/dashboard/search/SearchContainer';
import tw, { theme } from 'twin.macro';
import styled from 'styled-components';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Avatar from '@/components/Avatar';

const RightNavigation = styled.div`
    & > a,
    & > button,
    & > .navigation-link {
        ${tw`flex items-center h-full no-underline text-neutral-300 px-6 cursor-pointer transition-all duration-150`};

        &:active,
        &:hover {
            ${tw`text-neutral-100 bg-black`};
        }

        &:active,
        &:hover,
        &.active {
            box-shadow: inset 0 -2px ${theme`colors.cyan.600`.toString()};
        }
    }
`;

export default () => {
    const name = useStoreState((state: ApplicationStore) => state.settings.data!.name);
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const [isLoggingOut, setIsLoggingOut] = useState(false);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);

        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    return (
        <div className="w-full overflow-x-auto bg-neutral-900 shadow-md">
            <SpinnerOverlay visible={isLoggingOut} />
            <div className="mx-auto flex h-[3.5rem] w-full max-w-[1200px] items-center">
                <div id="logo" className="flex-1">
                    <Link
                        to="/"
                        className="px-4 font-header text-2xl text-neutral-200 no-underline transition-colors duration-150 hover:text-neutral-100"
                    >
                        {name}
                    </Link>
                </div>

                <RightNavigation className="flex h-full items-center justify-center">
                    <SearchContainer />

                    <Tooltip placement="bottom" content="Dashboard">
                        <NavLink to="/" end>
                            <FontAwesomeIcon icon={faLayerGroup} />
                        </NavLink>
                    </Tooltip>

                    <Tooltip placement="bottom" content="Account Settings">
                        <NavLink to="/account">
                            <span className="flex h-5 w-5 items-center">
                                <Avatar.User />
                            </span>
                        </NavLink>
                    </Tooltip>

                    {rootAdmin && (
                        <Tooltip placement="bottom" content="Admin">
                            <a href="/admin" rel="noreferrer">
                                <FontAwesomeIcon icon={faScrewdriverWrench} />
                            </a>
                        </Tooltip>
                    )}

                    <Tooltip placement="bottom" content="Sign Out">
                        <button onClick={onTriggerLogout}>
                            <FontAwesomeIcon icon={faSignOutAlt} />
                        </button>
                    </Tooltip>
                </RightNavigation>
            </div>
        </div>
    );
};
