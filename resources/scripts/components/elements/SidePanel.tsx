import React from 'react';
import tw from 'twin.macro';
import http from '@/api/http';
import * as Icon from 'react-feather';
import { useStoreState } from 'easy-peasy';
import styled from 'styled-components/macro';
import { NavLink, Link } from 'react-router-dom';
import ProgressBar from '@/components/elements/ProgressBar';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import SearchContainer from '@/components/dashboard/search/SearchContainer';

export default () => {
    const logo = useStoreState((state) => state.settings.data?.logo);
    const store = useStoreState((state) => state.storefront.data!.enabled);
    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);

    const onTriggerLogout = () => {
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    const PanelDiv = styled.div`
        ${tw`h-screen sticky bg-neutral-800 flex flex-col w-20 fixed top-0`};

        & > div {
            ${tw`mx-auto`};

            & > a,
            & > div {
                &:hover {
                    ${tw`text-neutral-100`};
                }

                &:active,
                &.active {
                    ${tw`text-green-600`};
                }
            }
        }
    `;

    return (
        <PanelDiv>
            <ProgressBar />
            <Link to={'/'}>
                <img className={'p-2'} src={logo ?? 'https://avatars.githubusercontent.com/u/91636558'} />
            </Link>
            <div>
                <div className={'navigation-link'}>
                    <div className={'bg-gray-700 rounded-lg p-2 my-8'}>
                        <SearchContainer size={32} />
                    </div>
                </div>
                <NavLink to={'/'} className={'navigation-link'} exact>
                    <Tooltip placement={'bottom'} content={'Servers'}>
                        <div className={'bg-gray-700 rounded-lg p-2 my-8'}>
                            <Icon.Server size={32} />
                        </div>
                    </Tooltip>
                </NavLink>
                <NavLink to={'/account'} className={'navigation-link'}>
                    <Tooltip placement={'bottom'} content={'Account'}>
                        <div className={'bg-gray-700 rounded-lg p-2 my-8'}>
                            <Icon.User size={32} />
                        </div>
                    </Tooltip>
                </NavLink>
                {store && (
                    <NavLink to={'/store'} className={'navigation-link'}>
                        <Tooltip placement={'bottom'} content={'Store'}>
                            <div className={'bg-gray-700 rounded-lg p-2 my-8'}>
                                <Icon.ShoppingCart size={32} />
                            </div>
                        </Tooltip>
                    </NavLink>
                )}
                {rootAdmin && (
                    <a href={'/admin'} className={'navigation-link'}>
                        <Tooltip placement={'bottom'} content={'Admin'}>
                            <div className={'bg-gray-700 rounded-lg p-2 my-8'}>
                                <Icon.Settings size={32} />
                            </div>
                        </Tooltip>
                    </a>
                )}
                <div id={'logo'}>
                    <button onClick={onTriggerLogout} className={'navigation-link'}>
                        <Tooltip placement={'bottom'} content={'Logout'}>
                            <div className={'flex flex-row fixed bottom-0 mb-8 bg-gray-700 rounded-lg p-2'}>
                                <Icon.LogOut size={32} />
                            </div>
                        </Tooltip>
                    </button>
                </div>
            </div>
        </PanelDiv>
    );
};
