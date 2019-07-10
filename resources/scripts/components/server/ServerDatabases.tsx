import React, { useEffect } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDatabase } from '@fortawesome/free-solid-svg-icons/faDatabase';
import getServerDatabases from '@/api/server/getServerDatabases';
import { useStoreState } from 'easy-peasy';

export default () => {
    useEffect(() => {
        getServerDatabases('s');
    }, []);

    return (
        <div className={'my-10'}>
            <div className={'flex rounded no-underline text-neutral-200 items-center bg-neutral-700 p-4 border border-transparent hover:border-neutral-500'}>
                <div className={'rounded-full bg-neutral-500 p-3'}>
                    <FontAwesomeIcon icon={faDatabase}/>
                </div>
                <div className={'w-1/2 ml-4'}>
                    <p className={'text-lg'}>sfgsfgd</p>
                </div>
            </div>
        </div>
    );
};
