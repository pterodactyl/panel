import React from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';

type Props = {
    schedule?: Schedule;
} & RequiredModalProps;

export default ({ schedule, ...props }: Props) => {
    return (
        <Modal {...props}>
            <p>Testing</p>
        </Modal>
    );
};
