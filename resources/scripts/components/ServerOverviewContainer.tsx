import * as React from 'react';
import { NavLink } from 'react-router-dom';

export default class ServerOverviewContainer extends React.PureComponent {
    render () {
        return (
            <div className={'mt-10'}>
                <NavLink className={'text-neutral-100 text-sm block mb-2 no-underline hover:underline'} to={'/account'}>Account</NavLink>
                <NavLink className={'text-neutral-100 text-sm block mb-2 no-underline hover:underline'} to={'/account/design'}>Design</NavLink>
            </div>
        );
    }
}
