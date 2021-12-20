import React from 'react';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { NavLink, Link } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCogs, faKey, faLayerGroup, faSignOutAlt, faSitemap, faUserCircle } from '@fortawesome/free-solid-svg-icons';
import { IconProp } from '@fortawesome/fontawesome-svg-core';
import styled from 'styled-components/macro';
import http from '@/api/http';

export function Category (props: { children: React.ReactNode }) {
    return (
        <div css={tw`flex flex-col my-4 space-y-2`}>
            {props.children}
        </div>);
}

export function SidePanelLink (props: { icon: IconProp, react?: boolean, link: string, exact?: boolean, title: string }) {
    return props.react ?? false ? (
        <NavLink to={props.link} exact={props.exact ?? false} css={tw`flex flex-row mx-auto`} className={'navigation-link'}>
            <FontAwesomeIcon icon={props.icon} size={'lg'} css={tw`mx-auto`}/>
        </NavLink>
    ) : (
        <a href={props.link} rel={'noreferrer'} css={tw`flex flex-row mx-auto`} className={'navigation-link'}>
            <FontAwesomeIcon icon={props.icon} size={'lg'} css={tw`mx-auto`}/>
        </a>
    );
}

export default (props: { children?: React.ReactNode }) => {
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);

    const onTriggerLogout = () => {
        http.post('/auth/logout').finally(() => {
            // @ts-ignore
            window.location = '/';
        });
    };

    const PanelDiv = styled.div`
        ${tw`h-screen bg-neutral-900 shadow-lg flex flex-col w-32 px-4 py-2 fixed top-0 left-0`};
    }
    `;

    return (
        <PanelDiv>
            <div id={'logo'}>
                <Link to={'/'}>
                    <img src={'https://camo.githubusercontent.com/a7f9ce191b39dbb9c33372a2df125c4171e2908420a6d6d8429d37af82804a37/68747470733a2f2f63646e2e707465726f64616374796c2e696f2f736974652d6173736574732f6c6f676f2d69636f6e2e706e67'} />
                </Link>
            </div>
            <Category>
                <SidePanelLink title={'Servers'} icon={faLayerGroup} react link={'/'} exact/>
                <br />
                <SidePanelLink title={'Account'} icon={faUserCircle} react link={'/account'}/>
                <br />
                <SidePanelLink title={'Account API'} icon={faSitemap} react link={'/account/api'}/>
                <br />
                <SidePanelLink title={'Account Security'} icon={faKey} react link={'/account/keys/security'}/>
                {rootAdmin &&
                    <>
                        <br />
                        <SidePanelLink title={'Administration'} icon={faCogs} link={'/admin'}/>
                    </>
                }
                <br />
                <button title={'Logout'} onClick={onTriggerLogout} css={tw`flex flex-row mx-auto`} className={'navigation-link'}>
                    <FontAwesomeIcon icon={faSignOutAlt} size={'lg'} css={tw`mx-auto`}/>
                </button>
            </Category>
            {props.children}
        </PanelDiv>
    );
};
