import React from 'react';
import { Task } from '@/api/server/schedules/getServerSchedules';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faCode } from '@fortawesome/free-solid-svg-icons/faCode';
import { faToggleOn } from '@fortawesome/free-solid-svg-icons/faToggleOn';

interface Props {
    task: Task;
}

export default ({ task }: Props) => {
    return (
        <div className={'flex items-center'}>
            <FontAwesomeIcon icon={task.action === 'command' ? faCode : faToggleOn} className={'text-lg text-white'}/>
            <div className={'flex-1'}>
                <p className={'ml-6 text-neutral-300 mb-2 uppercase text-xs'}>
                    {task.action === 'command' ? 'Send command' : 'Send power action'}
                </p>
                <code className={'ml-6 font-mono bg-neutral-800 rounded py-1 px-2 text-sm'}>
                    {task.payload}
                </code>
            </div>
            {task.sequenceId > 1 &&
            <div className={'mr-6'}>
                <p className={'text-center mb-1'}>
                    {task.timeOffset}s
                </p>
                <p className={'text-neutral-300 uppercase text-2xs'}>
                    Delay Run By
                </p>
            </div>
            }
            <div>
                <a
                    href={'#'}
                    className={'text-sm p-2 text-neutral-500 hover:text-red-600 transition-color duration-150'}
                >
                    <FontAwesomeIcon icon={faTrashAlt}/>
                </a>
            </div>
        </div>
    );
};
