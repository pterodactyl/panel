import * as React from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faLayerGroup } from '@fortawesome/free-solid-svg-icons/faLayerGroup';
import { faUserCircle } from '@fortawesome/free-solid-svg-icons/faUserCircle';
import { faSignOutAlt } from '@fortawesome/free-solid-svg-icons/faSignOutAlt';
import { faSwatchbook } from '@fortawesome/free-solid-svg-icons/faSwatchbook';
import { faCogs } from '@fortawesome/free-solid-svg-icons/faCogs';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data!);
    const name = useStoreState((state: ApplicationStore) => state.settings.data!.name);

    return (
        <div id={'navigation'}>
            <div className={'mx-auto w-full flex items-center'} style={{ maxWidth: '1200px', height: '3.5rem' }}>
                <div id={'logo'}>
                    <Link to={'/'}>
                        {name}
                    </Link>
                </div>
                <div className={'right-navigation'}>
                    <NavLink to={'/'} exact={true}>
                        <FontAwesomeIcon icon={faLayerGroup}/>
                    </NavLink>
                    <NavLink to={'/account'}>
                        <FontAwesomeIcon icon={faUserCircle}/>
                    </NavLink>
                    {user.rootAdmin &&
                    <a href={'/admin'} target={'_blank'}>
                        <FontAwesomeIcon icon={faCogs}/>
                    </a>
                    }
                    {process.env.NODE_ENV !== 'production' &&
                    <NavLink to={'/design'}>
                        <FontAwesomeIcon icon={faSwatchbook}/>
                    </NavLink>
                    }
                    <a href={'/auth/logout'}>
                        <FontAwesomeIcon icon={faSignOutAlt}/>
                    </a>
                </div>
            </div>
        </div>
    );
};
