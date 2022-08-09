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
    const store = useStoreState((state) => state.storefront.data!.enabled);
    const rootAdmin = useStoreState((state) => state.user.data!.rootAdmin);
    const logo = useStoreState((state) => state.settings.data!.logo);

    const onTriggerLogout = () => {
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    const PanelDiv = styled.div`
        ${tw`h-screen sticky bg-neutral-900 flex flex-col w-28 fixed top-0`};

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
                <img css={tw`p-4`} src={logo ?? 'https://avatars.githubusercontent.com/u/91636558'} />
            </Link>
            <div>
                <div css={tw`mx-auto mb-8`} className={'navigation-link'}>
                    <SearchContainer size={32} />
                </div>
                <NavLink to={'/'} className={'navigation-link'} exact>
                    <Tooltip placement={'bottom'} content={'Servers'}>
                        <Icon.Server size={32} css={tw`my-8`} />
                    </Tooltip>
                </NavLink>
                <NavLink to={'/account'} className={'navigation-link'}>
                    <Tooltip placement={'bottom'} content={'Account'}>
                        <Icon.User size={32} css={tw`my-8`} />
                    </Tooltip>
                </NavLink>
                {store && (
                    <NavLink to={'/store'} className={'navigation-link'}>
                        <Tooltip placement={'bottom'} content={'Store'}>
                            <Icon.ShoppingCart size={32} css={tw`my-8`} />
                        </Tooltip>
                    </NavLink>
                )}
                {rootAdmin && (
                    <a href={'/admin'} className={'navigation-link'}>
                        <Tooltip placement={'bottom'} content={'Admin'}>
                            <Icon.Settings size={32} css={tw`my-8`} />
                        </Tooltip>
                    </a>
                )}
                <div id={'logo'}>
                    <button onClick={onTriggerLogout} className={'navigation-link'}>
                        <Tooltip placement={'bottom'} content={'Logout'}>
                            <Icon.LogOut size={32} css={tw`flex flex-row fixed bottom-0 mb-8`} />
                        </Tooltip>
                    </button>
                </div>
            </div>
        </PanelDiv>
    );
};
