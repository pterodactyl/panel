import React, { useState } from 'react';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import Modal from '@/components/elements/Modal';

export default ({ onCreated }: { onCreated: (database: ServerDatabase) => void }) => {
    const [ visible, setVisible ] = useState(false);

    return (
        <React.Fragment>
            <Modal visible={visible} onDismissed={() => setVisible(false)}>
                <p>Testing</p>
            </Modal>
            <button className={'btn btn-primary btn-lg'} onClick={() => setVisible(true)}>
                Create Database
            </button>
        </React.Fragment>
    );
};
