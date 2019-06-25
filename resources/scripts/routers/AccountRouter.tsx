import * as React from 'react';
import { Link, NavLink, Route, RouteComponentProps } from 'react-router-dom';
import DesignElementsContainer from '@/components/account/DesignElementsContainer';
import AccountOverviewContainer from '@/components/account/AccountOverviewContainer';

export default ({ match }: RouteComponentProps) => (
    <div>
        <div className={'w-full bg-neutral-900 shadow-md'}>
            <div className={'mx-auto w-full flex items-center'} style={{ maxWidth: '1200px', height: '3.5rem' }}>
                <div className={'flex-1'}>
                    <Link
                        to={'/'}
                        className={'text-2xl font-header px-4 no-underline text-neutral-200 hover:text-neutral-100'}
                        style={{
                            transition: 'color 150ms linear',
                        }}
                    >
                        Pterodactyl
                    </Link>
                </div>
                <div className={'flex h-full items-center justify-center'}>
                    <NavLink
                        to={'/'}
                        exact={true}
                        className={'flex items-center h-full no-underline text-neutral-300 hover:text-neutral-100 hover:bg-black px-4'}
                        style={{
                            transition: 'background-color 150ms linear, color 150ms linear',
                        }}
                    >
                        Dashboard
                    </NavLink>
                    <NavLink
                        to={'/account'}
                        className={'flex items-center h-full no-underline text-neutral-300 hover:text-neutral-100 hover:bg-black px-4'}
                        style={{
                            transition: 'background-color 150ms linear, color 150ms linear',
                        }}
                    >
                        Account
                    </NavLink>
                </div>
            </div>
        </div>
        <div className={'w-full mx-auto'} style={{ maxWidth: '1200px' }}>
            <Route path={`${match.path}/`} component={AccountOverviewContainer} exact/>
            <Route path={`${match.path}/design`} component={DesignElementsContainer} exact/>
        </div>
    </div>
);
