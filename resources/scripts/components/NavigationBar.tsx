import * as React from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faLayerGroup } from '@fortawesome/free-solid-svg-icons/faLayerGroup';
import { faUserCircle } from '@fortawesome/free-solid-svg-icons/faUserCircle';
import { faSignOutAlt } from '@fortawesome/free-solid-svg-icons/faSignOutAlt';
import { faSwatchbook } from '@fortawesome/free-solid-svg-icons/faSwatchbook';

export default () => (
    <div id={'navigation'}>
        <div className={'mx-auto w-full flex items-center'} style={{ maxWidth: '1200px', height: '3.5rem' }}>
            <div id={'logo'}>
                <Link to={'/'}>
                    Pterodactyl
                </Link>
            </div>
            <div className={'right-navigation'}>
                <NavLink to={'/'} exact={true}>
                    <FontAwesomeIcon icon={faLayerGroup}/>
                </NavLink>
                <NavLink to={'/account'}>
                    <FontAwesomeIcon icon={faUserCircle}/>
                </NavLink>
                {process.env.NODE_ENV !== 'production' &&
                <NavLink to={'/design'}>
                    <FontAwesomeIcon icon={faSwatchbook}/>
                </NavLink>
                }
                <NavLink to={'/auth/logout'}>
                    <FontAwesomeIcon icon={faSignOutAlt}/>
                </NavLink>
            </div>
        </div>
    </div>
);
