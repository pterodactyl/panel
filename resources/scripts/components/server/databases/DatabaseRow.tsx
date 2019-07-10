import React from 'react';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDatabase } from '@fortawesome/free-solid-svg-icons/faDatabase';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faEye } from '@fortawesome/free-solid-svg-icons/faEye';

export default ({ database }: { database: ServerDatabase }) => {
    return (
        <div className={'grey-row-box no-hover'}>
            <div className={'icon'}>
                <FontAwesomeIcon icon={faDatabase}/>
            </div>
            <div className={'flex-1 ml-4'}>
                <p className={'text-lg'}>{database.name}</p>
            </div>
            <div className={'ml-6'}>
                <p className={'text-center text-xs text-neutral-500 uppercase mb-1 select-none'}>Endpoint:</p>
                <p className={'text-center text-sm'}>{database.connectionString}</p>
            </div>
            <div className={'ml-6'}>
                <p className={'text-center text-xs text-neutral-500 uppercase mb-1 select-none'}>Connections From:</p>
                <p className={'text-center text-sm'}>{database.allowConnectionsFrom}</p>
            </div>
            <div className={'ml-6'}>
                <p className={'text-center text-xs text-neutral-500 uppercase mb-1 select-none'}>Username:</p>
                <p className={'text-center text-sm'}>{database.username}</p>
            </div>
            <div className={'ml-6'}>
                <button className={'btn btn-sm btn-secondary mr-2'}>
                    <FontAwesomeIcon icon={faEye} fixedWidth={true}/>
                </button>
                <button className={'btn btn-sm btn-secondary btn-red'}>
                    <FontAwesomeIcon icon={faTrashAlt} fixedWidth={true}/>
                </button>
            </div>
        </div>
    );
};
