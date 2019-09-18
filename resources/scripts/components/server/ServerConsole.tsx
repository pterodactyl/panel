import React from 'react';
import Console from '@/components/server/Console';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faServer } from '@fortawesome/free-solid-svg-icons/faServer';
import { faCircle } from '@fortawesome/free-solid-svg-icons/faCircle';
import classNames from 'classnames';

export default () => {
    const server = ServerContext.useStoreState(state => state.server.data!);
    const status = ServerContext.useStoreState(state => state.status.value);

    return (
        <div className={'my-10 flex'}>
            <div className={'flex-1 ml-4'}>
                <div className={'rounded shadow-md bg-neutral-700'}>
                    <div className={'bg-neutral-900 rounded-t p-3 border-b border-black'}>
                        <p className={'text-sm uppercase'}>
                            <FontAwesomeIcon icon={faServer} className={'mr-1 text-neutral-300'}/> {server.name}
                        </p>
                    </div>
                    <div className={'p-3'}>
                        <p className={'text-xs uppercase'}>
                            <FontAwesomeIcon
                                icon={faCircle}
                                className={classNames('mr-1', {
                                    'text-red-500': status === 'offline',
                                    'text-yellow-500': ['running', 'offline'].indexOf(status) < 0,
                                    'text-green-500': status === 'running',
                                })}
                            />
                            &nbsp;{status}
                        </p>
                    </div>
                </div>
            </div>
            <div className={'mx-4 w-3/4 mr-4'}>
                <Console/>
            </div>
        </div>
    );
};
