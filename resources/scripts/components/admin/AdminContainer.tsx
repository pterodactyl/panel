import React, { useState } from 'react';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { NavLink, RouteComponentProps } from 'react-router-dom';
import styled from 'styled-components/macro';

const Sidebar = styled.div`
    ${tw`h-screen flex flex-col items-center bg-neutral-900 overflow-x-hidden`};
`;

const Navigation = styled.div`
    ${tw`w-full flex flex-col px-4`};

    & > span {
        ${tw`font-header font-medium text-xs text-neutral-300 whitespace-no-wrap uppercase ml-4 mb-1`};

        &:not(:first-of-type) {
            ${tw`mt-4`};
        }
    }

    & > a {
        ${tw`h-10 w-full flex flex-row items-center text-neutral-300 px-4 transition-all duration-100 cursor-pointer`};

        & > svg {
            ${tw`h-6 w-6`};
        }

        & > span {
            ${tw`font-header font-medium text-lg leading-none ml-3`};
        }

        &:hover {
            ${tw`text-neutral-50`};
        }

        &:active, &.active {
            ${tw`text-neutral-50 bg-neutral-800 rounded`}
        }
    }
`;

const CollapsedNavigation = styled.div`
    ${tw`w-full flex flex-col px-4`};

    & > span {
        ${tw`opacity-0 whitespace-no-wrap`};

        &:not(:first-of-type) {
            ${tw`mt-4`};
        }
    }

    & > a {
        ${tw`h-10 w-full flex flex-row items-center justify-center text-neutral-300 transition-all duration-100 cursor-pointer`};

        & > svg {
            ${tw`h-6 w-6 flex flex-shrink-0`};
        }

        & > span {
            ${tw`hidden`};
        }

        &:hover {
            ${tw`text-neutral-50`};
        }

        &:active, &.active {
            ${tw`text-neutral-50 bg-neutral-800 rounded`}
        }
    }
`;

type Props = {
    url: string,
};

const NavItems = ({ url }: Props) => {
    return (
        <>
            <span>Administration</span>

            <NavLink to={`${url}`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                <span>Overview</span>
            </NavLink>
            <NavLink to={`${url}/settings`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span>Settings</span>
            </NavLink>
            <NavLink to={`${url}/api`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                <span>API Keys</span>
            </NavLink>

            <span>Management</span>

            <NavLink to={`${url}/databases`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                <span>Databases</span>
            </NavLink>
            <NavLink to={`${url}/locations`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Locations</span>
            </NavLink>
            <NavLink to={`${url}/nodes`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                <span>Nodes</span>
            </NavLink>
            <NavLink to={`${url}/servers`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span>Servers</span>
            </NavLink>
            <NavLink to={`${url}/users`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span>Users</span>
            </NavLink>

            <span>Service Management</span>

            <NavLink to={`${url}/nests`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                <span>Nests</span>
            </NavLink>
            <NavLink to={`${url}/packs`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                <span>Packs</span>
            </NavLink>
            <NavLink to={`${url}/mounts`}>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                <span>Mounts</span>
            </NavLink>
        </>
    );
};

export default ({ match }: RouteComponentProps<{ id: string }>) => {
    const name = useStoreState((state: ApplicationStore) => state.settings.data!.name);
    const [ collapsed, setCollapsed ] = useState<boolean>();

    return (
        <div css={tw`h-screen w-screen overflow-x-hidden`}>
            <Sidebar style={ !collapsed ? { width: '287px' } : { width: '70px' } }>
                <div css={tw`h-16 w-full flex flex-col items-center justify-center mt-1 mb-3 select-none cursor-pointer`} onClick={
                    () => {
                        setCollapsed(!collapsed);
                    }
                }>
                    { !collapsed ?
                        <h1 css={tw`text-2xl text-neutral-50 whitespace-no-wrap`}>{name}</h1>
                        :
                        <img src={'/favicons/android-icon-48x48.png'} />
                    }
                </div>

                {!collapsed ?
                    <>
                        <Navigation>
                            <NavItems url={match.url} />
                        </Navigation>

                        <div css={tw`h-16 w-full flex flex-row items-center bg-neutral-700 mt-auto px-5`}>
                            <img src={'https://cdn.krygon.app/avatars/52564280420073473/7db9f06013ec39f7fa5c1e79241c43afa1f152d82cbb193ecaab7753b9a3e61e?size=64'} alt="Profile Picture" css={tw`h-10 w-10 rounded-full`} />

                            <div css={tw`flex flex-col ml-4`}>
                                <span css={tw`font-header font-medium text-sm text-neutral-50 leading-tight`}>Matthew Penner</span>
                                <span css={tw`font-header font-normal text-xs text-neutral-300 leading-tight`}>Super Administrator</span>
                            </div>

                            <NavLink to={'/auth/logout'} css={tw`h-8 w-8 flex items-center justify-center text-neutral-300 hover:text-neutral-50 ml-auto transition-all duration-100`}>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" css={tw`h-6 w-6`}><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            </NavLink>
                        </div>
                    </>
                    :
                    <>
                        <CollapsedNavigation>
                            <NavItems url={match.url} />
                        </CollapsedNavigation>

                        <NavLink to={'/auth/logout'} css={tw`h-8 w-8 flex items-center justify-center text-neutral-300 hover:text-neutral-50 mt-auto mb-3 transition-all duration-100`}>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" css={tw`h-6 w-6`}><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </NavLink>

                        <div css={tw`h-16 w-full flex items-center justify-center bg-neutral-700`}>
                            <img src={'https://cdn.krygon.app/avatars/52564280420073473/7db9f06013ec39f7fa5c1e79241c43afa1f152d82cbb193ecaab7753b9a3e61e?size=64'} alt="Profile Picture" css={tw`h-10 w-10 rounded-full`} />
                        </div>
                    </>
                }
            </Sidebar>
        </div>
    );
};
