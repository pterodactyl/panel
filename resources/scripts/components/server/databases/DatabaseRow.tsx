import React, { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDatabase, faEye, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import Modal from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ServerContext } from '@/state/server';
import deleteServerDatabase from '@/api/server/databases/deleteServerDatabase';
import { httpErrorToHuman } from '@/api/http';
import RotatePasswordButton from '@/components/server/databases/RotatePasswordButton';
import Can from '@/components/elements/Can';
import { ServerDatabase } from '@/api/server/databases/getServerDatabases';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Label from '@/components/elements/Label';
import Input from '@/components/elements/Input';
import GreyRowBox from '@/components/elements/GreyRowBox';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { useTranslation } from 'react-i18next';

interface Props {
    database: ServerDatabase;
    className?: string;
}

export default ({ database, className }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { addError, clearFlashes } = useFlash();
    const [ visible, setVisible ] = useState(false);
    const [ connectionVisible, setConnectionVisible ] = useState(false);
    const { t } = useTranslation('server');

    const appendDatabase = ServerContext.useStoreActions(actions => actions.databases.appendDatabase);
    const removeDatabase = ServerContext.useStoreActions(actions => actions.databases.removeDatabase);

    const schema = object().shape({
        confirm: string()
            .required(t('database_name_must_provided'))
            .oneOf([ database.name.split('_', 2)[1], database.name ], t('database_name_must_provided')),
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

    return (
        <>
            <Formik
                onSubmit={submit}
                initialValues={{ confirm: '' }}
                validationSchema={schema}
                isInitialValid={false}
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
                            <FlashMessageRender byKey={'database:delete'} css={tw`mb-6`}/>
                            <h2 css={tw`text-2xl mb-6`}>{t('confirm_database_deletion')}</h2>
                            <p css={tw`text-sm`}>{t('database_delete_text', { database: '<strong>' + database.name + '</strong>' })}</p>
                            <Form css={tw`m-0 mt-6`}>
                                <Field
                                    type={'text'}
                                    id={'confirm_name'}
                                    name={'confirm'}
                                    label={t('confirm_database_name')}
                                    description={t('enter_database_name')}
                                />
                                <div css={tw`mt-6 text-right`}>
                                    <Button
                                        type={'button'}
                                        isSecondary
                                        css={tw`mr-2`}
                                        onClick={() => setVisible(false)}
                                    >
                                        {t('cancel')}
                                    </Button>
                                    <Button
                                        type={'submit'}
                                        color={'red'}
                                        disabled={!isValid}
                                    >
                                        {t('delete_database')}
                                    </Button>
                                </div>
                            </Form>
                        </Modal>
                    )
                }
            </Formik>
            <Modal visible={connectionVisible} onDismissed={() => setConnectionVisible(false)}>
                <FlashMessageRender byKey={'database-connection-modal'} css={tw`mb-6`}/>
                <h3 css={tw`mb-6 text-2xl`}>Database connection details</h3>
                <div>
                    <Label>Endpoint</Label>
                    <CopyOnClick text={database.connectionString}><Input type={'text'} readOnly value={database.connectionString} /></CopyOnClick>
                </div>
                <div css={tw`mt-6`}>
                    <Label>Connections from</Label>
                    <Input type={'text'} readOnly value={database.allowConnectionsFrom} />
                </div>
                <div css={tw`mt-6`}>
                    <Label>Username</Label>
                    <CopyOnClick text={database.username}><Input type={'text'} readOnly value={database.username} /></CopyOnClick>
                </div>
                <Can action={'database.view_password'}>
                    <div css={tw`mt-6`}>
                        <Label>Password</Label>
                        <CopyOnClick text={database.password}><Input type={'text'} readOnly value={database.password}/></CopyOnClick>
                    </div>
                </Can>
                <div css={tw`mt-6`}>
                    <Label>JDBC Connection String</Label>
                    <CopyOnClick text={`jdbc:mysql://${database.username}:${database.password}@${database.connectionString}/${database.name}`}>
                        <Input
                            type={'text'}
                            readOnly
                            value={`jdbc:mysql://${database.username}:${database.password}@${database.connectionString}/${database.name}`}
                        />
                    </CopyOnClick>
                </div>
                <div css={tw`mt-6 text-right`}>
                    <Can action={'database.update'}>
                        <RotatePasswordButton databaseId={database.id} onUpdate={appendDatabase}/>
                    </Can>
                    <Button isSecondary onClick={() => setConnectionVisible(false)}>
                        Close
                    </Button>
                </div>
            </Modal>
            <GreyRowBox $hoverable={false} className={className} css={tw`mb-2`}>
                <div css={tw`hidden md:block`}>
                    <FontAwesomeIcon icon={faDatabase} fixedWidth/>
                </div>
                <div css={tw`flex-1 ml-4`}>
                    <CopyOnClick text={database.name}><p css={tw`text-lg`}>{database.name}</p></CopyOnClick>
                </div>
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <CopyOnClick text={database.connectionString}><p css={tw`text-sm`}>{database.connectionString}</p></CopyOnClick>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Endpoint</p>
                </div>
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <p css={tw`text-sm`}>{database.allowConnectionsFrom}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Connections from</p>
                </div>
                <div css={tw`ml-8 text-center hidden md:block`}>
                    <CopyOnClick text={database.username}><p css={tw`text-sm`}>{database.username}</p></CopyOnClick>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Username</p>
                </div>
                <div css={tw`ml-8`}>
                    <Button isSecondary css={tw`mr-2`} onClick={() => setConnectionVisible(true)}>
                        <FontAwesomeIcon icon={faEye} fixedWidth/>
                    </Button>
                    <Can action={'database.delete'}>
                        <Button color={'red'} isSecondary onClick={() => setVisible(true)}>
                            <FontAwesomeIcon icon={faTrashAlt} fixedWidth/>
                        </Button>
                    </Can>
                </div>
            </GreyRowBox>
        </>
    );
};
