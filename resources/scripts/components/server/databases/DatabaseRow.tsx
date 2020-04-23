import React, { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDatabase } from '@fortawesome/free-solid-svg-icons/faDatabase';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faEye } from '@fortawesome/free-solid-svg-icons/faEye';
import classNames from 'classnames';
import Modal from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import deleteServerDatabase from '@/api/server/deleteServerDatabase';
import { httpErrorToHuman } from '@/api/http';
import RotatePasswordButton from '@/components/server/databases/RotatePasswordButton';
import Can from '@/components/elements/Can';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';

interface Props {
    database: ServerDatabase;
    className?: string;
}

export default ({ database, className }: Props) => {
    const { uuid } = useServer();
    const { addError, clearFlashes } = useFlash();
    const [ visible, setVisible ] = useState(false);
    const [ connectionVisible, setConnectionVisible ] = useState(false);

    const appendDatabase = ServerContext.useStoreActions(actions => actions.databases.appendDatabase);
    const removeDatabase = ServerContext.useStoreActions(actions => actions.databases.removeDatabase);

    const schema = object().shape({
        confirm: string()
            .required('The database name must be provided.')
            .oneOf([ database.name.split('_', 2)[1], database.name ], 'The database name must be provided.'),
    });

    const submit = (values: { confirm: string }, { setSubmitting }: FormikHelpers<{ confirm: string }>) => {
        clearFlashes();
        deleteServerDatabase(uuid, database.id)
            .then(() => {
                setVisible(false);
                setTimeout(() => removeDatabase(database.id), 150);
            })
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                addError({ key: 'database:delete', message: httpErrorToHuman(error) });
            });
    };
    if (!database.maxConnections){
        database.maxConnections = "Unlimited"
    }
    
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
                            onDismissed={() => {
                                setVisible(false);
                                resetForm();
                            }}
                        >
                            <FlashMessageRender byKey={'database:delete'} className={'mb-6'}/>
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
                <Can action={'database.view_password'}>
                    <div>
                        <label className={'input-dark-label'}>Password</label>
                        <input type={'text'} className={'input-dark'} readOnly={true} value={database.password}/>
                    </div>
                </Can>
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
                    <Can action={'database.update'}>
                        <RotatePasswordButton databaseId={database.id} onUpdate={appendDatabase}/>
                    </Can>
                    <button className={'btn btn-sm btn-secondary'} onClick={() => setConnectionVisible(false)}>
                        Close
                    </button>
                </div>
            </Modal>
            <div className={classNames('grey-row-box no-hover', className)}>
                <div className={'icon'}>
                    <FontAwesomeIcon icon={faDatabase} fixedWidth={true}/>
                </div>
                <div className={'flex-1 ml-4'}>
                    <p className={'text-lg'}>{database.name}</p>
                </div>
                <div className={'ml-8 text-center'}>
                    <p className={'text-sm'}>{database.connectionString}</p>
                    <p className={'mt-1 text-2xs text-neutral-500 uppercase select-none'}>Endpoint</p>
                </div>
                <div className={'ml-8 text-center'}>
                    <p className={'text-sm'}>{database.allowConnectionsFrom}</p>
                    <p className={'mt-1 text-2xs text-neutral-500 uppercase select-none'}>Connections from</p>
                </div>
                <div className={'ml-8 text-center'}>
                    <p className={'text-sm'}>{database.username}</p>
                    <p className={'mt-1 text-2xs text-neutral-500 uppercase select-none'}>Username</p>
                </div>
                <div className={'ml-8 text-center'}>
                    <p className={'text-sm'}>{database.maxConnections}</p>
                    <p className={'mt-1 text-2xs text-neutral-500 uppercase select-none'}>Max Connections</p>
                </div>
                <div className={'ml-8'}>
                    <button className={'btn btn-sm btn-secondary mr-2'} onClick={() => setConnectionVisible(true)}>
                        <FontAwesomeIcon icon={faEye} fixedWidth={true}/>
                    </button>
                    <Can action={'database.delete'}>
                        <button className={'btn btn-sm btn-secondary btn-red'} onClick={() => setVisible(true)}>
                            <FontAwesomeIcon icon={faTrashAlt} fixedWidth={true}/>
                        </button>
                    </Can>
                </div>
            </div>
        </React.Fragment>
    );
};
