import type { Action, Actions } from 'easy-peasy';
import { action, createContextStore, useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import tw from 'twin.macro';
import { number, object, string } from 'yup';

import type { Database } from '@/api/admin/databases/getDatabases';
import getDatabase from '@/api/admin/databases/getDatabase';
import updateDatabase from '@/api/admin/databases/updateDatabase';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import AdminBox from '@/components/admin/AdminBox';
import { Button } from '@/components/elements/button';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DatabaseDeleteButton from '@/components/admin/databases/DatabaseDeleteButton';
import type { ApplicationStore } from '@/state';

interface ctx {
    database: Database | undefined;
    setDatabase: Action<ctx, Database | undefined>;
}

export const Context = createContextStore<ctx>({
    database: undefined,

    setDatabase: action((state, payload) => {
        state.database = payload;
    }),
});

export interface Values {
    name: string;
    host: string;
    port: number;
    username: string;
    password: string;
}

export interface Params {
    title: string;
    initialValues?: Values;
    children?: React.ReactNode;

    onSubmit: (values: Values, helpers: FormikHelpers<Values>) => void;
}

export const InformationContainer = ({ title, initialValues, children, onSubmit }: Params) => {
    const submit = (values: Values, helpers: FormikHelpers<Values>) => {
        onSubmit(values, helpers);
    };

    if (!initialValues) {
        initialValues = {
            name: '',
            host: '',
            port: 3306,
            username: '',
            password: '',
        };
    }

    return (
        <Formik
            onSubmit={submit}
            initialValues={initialValues}
            validationSchema={object().shape({
                name: string().required().max(191),
                host: string().max(255),
                port: number().min(2).max(65534),
                username: string().min(1).max(32),
                password: string(),
            })}
        >
            {({ isSubmitting, isValid }) => (
                <>
                    <AdminBox title={title} css={tw`relative`}>
                        <SpinnerOverlay visible={isSubmitting} />

                        <Form css={tw`mb-0`}>
                            <div>
                                <Field id={'name'} name={'name'} label={'Name'} type={'text'} />
                            </div>

                            <div css={tw`md:w-full md:flex md:flex-row mt-6`}>
                                <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mt-6 md:mt-0`}>
                                    <Field id={'host'} name={'host'} label={'Host'} type={'text'} />
                                </div>

                                <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mt-6 md:mt-0`}>
                                    <Field id={'port'} name={'port'} label={'Port'} type={'text'} />
                                </div>
                            </div>

                            <div css={tw`md:w-full md:flex md:flex-row mt-6`}>
                                <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mt-6 md:mt-0`}>
                                    <Field id={'username'} name={'username'} label={'Username'} type={'text'} />
                                </div>

                                <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mt-6 md:mt-0`}>
                                    <Field
                                        id={'password'}
                                        name={'password'}
                                        label={'Password'}
                                        type={'password'}
                                        placeholder={'••••••••'}
                                    />
                                </div>
                            </div>

                            <div css={tw`w-full flex flex-row items-center mt-6`}>
                                {children}
                                <div css={tw`flex ml-auto`}>
                                    <Button type="submit" disabled={isSubmitting || !isValid}>
                                        Save Changes
                                    </Button>
                                </div>
                            </div>
                        </Form>
                    </AdminBox>
                </>
            )}
        </Formik>
    );
};

const EditInformationContainer = () => {
    const navigate = useNavigate();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const database = Context.useStoreState(state => state.database);
    const setDatabase = Context.useStoreActions(actions => actions.setDatabase);

    if (database === undefined) {
        return <></>;
    }

    const submit = ({ name, host, port, username, password }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('database');

        updateDatabase(database.id, name, host, port, username, password || undefined)
            .then(() => setDatabase({ ...database, name, host, port, username }))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'database', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <InformationContainer
            title={'Edit Database'}
            initialValues={{
                name: database.name,
                host: database.host,
                port: database.port,
                username: database.username,
                password: '',
            }}
            onSubmit={submit}
        >
            <div css={tw`flex`}>
                <DatabaseDeleteButton databaseId={database.id} onDeleted={() => navigate('/admin/databases')} />
            </div>
        </InformationContainer>
    );
};

const DatabaseEditContainer = () => {
    const params = useParams<'id'>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const [loading, setLoading] = useState(true);

    const database = Context.useStoreState(state => state.database);
    const setDatabase = Context.useStoreActions(actions => actions.setDatabase);

    useEffect(() => {
        clearFlashes('database');

        getDatabase(Number(params.id))
            .then(database => setDatabase(database))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'database', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || database === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'database'} css={tw`mb-4`} />

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'} />
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Database - ' + database.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{database.name}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        {database.getAddress()}
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'database'} css={tw`mb-4`} />

            <EditInformationContainer />
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <DatabaseEditContainer />
        </Context.Provider>
    );
};
