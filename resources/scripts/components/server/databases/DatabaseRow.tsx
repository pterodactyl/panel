import React, { useState } from 'react';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDatabase } from '@fortawesome/free-solid-svg-icons/faDatabase';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faEye } from '@fortawesome/free-solid-svg-icons/faEye';
import classNames from 'classnames';
import Modal from '@/components/elements/Modal';
import { Form, Formik, FormikActions } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { ServerContext } from '@/state/server';
import deleteServerDatabase from '@/api/server/deleteServerDatabase';
import { httpErrorToHuman } from '@/api/http';
import RotatePasswordButton from '@/components/server/databases/RotatePasswordButton';

interface Props {
    databaseId: string | number;
    className?: string;
    onDelete: () => void;
}

export default ({ databaseId, className, onDelete }: Props) => {
    const [visible, setVisible] = useState(false);
    const database = ServerContext.useStoreState(state => state.databases.items.find(item => item.id === databaseId));
    const appendDatabase = ServerContext.useStoreActions(actions => actions.databases.appendDatabase);
    const [connectionVisible, setConnectionVisible] = useState(false);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const server = ServerContext.useStoreState(state => state.server.data!);

    if (!database) {
        return null;
    }

    const schema = object().shape({
        confirm: string()
            .required('The database name must be provided.')
            .oneOf([database.name.split('_', 2)[1], database.name], 'The database name must be provided.'),
    });

    const submit = (values: { confirm: string }, { setSubmitting }: FormikActions<{ confirm: string }>) => {
        clearFlashes();
        deleteServerDatabase(server.uuid, database.id)
            .then(() => {
                setVisible(false);
                setTimeout(() => onDelete(), 150);
            })
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                addFlash({
                    key: 'delete-database-modal',
                    type: 'error',
                    title: 'Error',
                    message: httpErrorToHuman(error),
                });
            });
    };

    return (
        <React.Fragment>
            <Formik
                onSubmit={submit}
                initialValues={{ confirm: '' }}
                validationSchema={schema}
            >
                {
                    ({ isSubmitting, isValid, resetForm }) => (
                        <Modal
                            visible={visible}
                            dismissable={!isSubmitting}
                            showSpinnerOverlay={isSubmitting}
                            onDismissed={() => { setVisible(false); resetForm(); }}
                        >
                            <FlashMessageRender byKey={'delete-database-modal'} className={'mb-6'}/>
                            <h3 className={'mb-6'}>Confirm database deletion</h3>
                            <p className={'text-sm'}>
                                Deleting a database is a permanent action, it cannot be undone. This will permanetly
                                delete the <strong>{database.name}</strong> database and remove all associated data.
                            </p>
                            <Form className={'m-0 mt-6'}>
                                <Field
                                    type={'text'}
                                    id={'confirm_name'}
                                    name={'confirm'}
                                    label={'Confirm Database Name'}
                                    description={'Enter the database name to confirm deletion.'}
                                />
                                <div className={'mt-6 text-right'}>
                                    <button
                                        type={'button'}
                                        className={'btn btn-sm btn-secondary mr-2'}
                                        onClick={() => setVisible(false)}
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type={'submit'}
                                        className={'btn btn-sm btn-red'}
                                        disabled={!isValid}
                                    >
                                        Delete Database
                                    </button>
                                </div>
                            </Form>
                        </Modal>
                    )
                }
            </Formik>
            <Modal visible={connectionVisible} onDismissed={() => setConnectionVisible(false)}>
                <FlashMessageRender byKey={'database-connection-modal'} className={'mb-6'}/>
                <h3 className={'mb-6'}>Database connection details</h3>
                <div>
                    <label className={'input-dark-label'}>Password</label>
                    <input type={'text'} className={'input-dark'} readOnly={true} value={database.password}/>
                </div>
                <div className={'mt-6'}>
                    <label className={'input-dark-label'}>JBDC Connection String</label>
                    <input
                        type={'text'}
                        className={'input-dark'}
                        readOnly={true}
                        value={`jdbc:mysql://${database.username}:${database.password}@${database.connectionString}/${database.name}`}
                    />
                </div>
                <div className={'mt-6 text-right'}>
                    <RotatePasswordButton databaseId={database.id} onUpdate={appendDatabase}/>
                    <button className={'btn btn-sm btn-secondary'} onClick={() => setConnectionVisible(false)}>
                        Close
                    </button>
                </div>
            </Modal>
            <div className={classNames('grey-row-box no-hover', className)}>
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
                    <p className={'text-center text-xs text-neutral-500 uppercase mb-1 select-none'}>
                        Connections From:
                    </p>
                    <p className={'text-center text-sm'}>{database.allowConnectionsFrom}</p>
                </div>
                <div className={'ml-6'}>
                    <p className={'text-center text-xs text-neutral-500 uppercase mb-1 select-none'}>Username:</p>
                    <p className={'text-center text-sm'}>{database.username}</p>
                </div>
                <div className={'ml-6'}>
                    <button className={'btn btn-sm btn-secondary mr-2'} onClick={() => setConnectionVisible(true)}>
                        <FontAwesomeIcon icon={faEye} fixedWidth={true}/>
                    </button>
                    <button className={'btn btn-sm btn-secondary btn-red'} onClick={() => setVisible(true)}>
                        <FontAwesomeIcon icon={faTrashAlt} fixedWidth={true}/>
                    </button>
                </div>
            </div>
        </React.Fragment>
    );
};
