import React, { useState } from 'react';
import Modal from '@/components/elements/Modal';
import deleteSchedule from '@/api/server/schedules/deleteSchedule';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';

interface Props {
    scheduleId: number;
    onDeleted: () => void;
}

export default ({ scheduleId, onDeleted }: Props) => {
    const [ visible, setVisible ] = useState(false);
    const [ isLoading, setIsLoading ] = useState(false);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const onDelete = () => {
        setIsLoading(true);
        clearFlashes('schedules');
        deleteSchedule(uuid, scheduleId)
            .then(() => {
                setIsLoading(false);
                onDeleted();
            })
            .catch(error => {
                console.error(error);

                addError({ key: 'schedules', message: httpErrorToHuman(error) });
                setIsLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <Modal
                visible={visible}
                onDismissed={() => setVisible(false)}
                showSpinnerOverlay={isLoading}
            >
                <h3 className={'mb-6'}>Delete schedule</h3>
                <p className={'text-sm'}>
                    Are you sure you want to delete this schedule? All tasks will be removed and any running processes
                    will be terminated.
                </p>
                <div className={'mt-6 flex justify-end'}>
                    <button
                        className={'btn btn-secondary btn-sm mr-4'}
                        onClick={() => setVisible(false)}
                    >
                        Cancel
                    </button>
                    <button
                        className={'btn btn-red btn-sm'}
                        onClick={() => onDelete()}
                    >
                        Yes, delete schedule
                    </button>
                </div>
            </Modal>
            <button className={'btn btn-red btn-secondary btn-sm mr-4'} onClick={() => setVisible(true)}>
                Delete
            </button>
        </>
    );
};
