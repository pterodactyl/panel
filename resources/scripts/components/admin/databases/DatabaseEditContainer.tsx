import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useHistory, useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Database } from '@/api/admin/databases/getDatabases';
import getDatabase from '@/api/admin/databases/getDatabase';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import { number, object, string } from 'yup';
import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers } from 'formik';
import updateDatabase from '@/api/admin/databases/updateDatabase';
import DatabaseDeleteButton from '@/components/admin/databases/DatabaseDeleteButton';

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

interface Values {
    name: string;
    host: string;
    port: number;
    username: string;
    password: string;
}

const EditInformationContainer = () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const database = Context.useStoreState(state => state.database);
    const setDatabase = Context.useStoreActions(actions => actions.setDatabase);

    if (database === undefined) {
        return (
            <></>
        );
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
        <Formik
            onSubmit={submit}
            initialValues={{
                name: database.name,
                host: database.host,
                port: database.port,
                username: database.username,
                password: '',
            }}
            validationSchema={object().shape({
                name: string().required().max(191),
                host: string().max(255),
                port: number().min(2).max(65534),
                username: string().min(1).max(32),
                password: string(),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <AdminBox title={'Edit Database'} css={tw`relative`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div>
                                    <Field
                                        id={'name'}
                                        name={'name'}
                                        label={'Name'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`w-full flex flex-row mt-6`}>
                                    <div css={tw`w-full flex flex-col mr-4`}>
                                        <Field
                                            id={'host'}
                                            name={'host'}
                                            label={'Host'}
                                            type={'text'}
                                        />
                                    </div>

                                    <div css={tw`w-full flex flex-col ml-4`}>
                                        <Field
                                            id={'port'}
                                            name={'port'}
                                            label={'Port'}
                                            type={'text'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`w-full flex flex-row mt-6`}>
                                    <div css={tw`w-full flex flex-col mr-4`}>
                                        <Field
                                            id={'username'}
                                            name={'username'}
                                            label={'Username'}
                                            type={'text'}
                                        />
                                    </div>

                                    <div css={tw`w-full flex flex-col ml-4`}>
                                        <Field
                                            id={'password'}
                                            name={'password'}
                                            label={'Password'}
                                            type={'password'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`w-full flex flex-row items-center mt-6`}>
                                    <div css={tw`flex`}>
                                        <DatabaseDeleteButton
                                            databaseId={database.id}
                                            onDeleted={() => history.push('/admin/databases')}
                                        />
                                    </div>

                                    <div css={tw`flex ml-auto`}>
                                        <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                            Save
                                        </Button>
                                    </div>
                                </div>
                            </Form>
                        </AdminBox>
                    </React.Fragment>
                )
            }
        </Formik>
    );
};

const DatabaseEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const database = Context.useStoreState(state => state.database);
    const setDatabase = Context.useStoreActions(actions => actions.setDatabase);

    useEffect(() => {
        clearFlashes('database');

        getDatabase(Number(match.params?.id))
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
                <FlashMessageRender byKey={'database'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Database - ' + database.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{database.name}</h2>
                    <p css={tw`text-base text-neutral-400`}>{database.getAddress()}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'database'} css={tw`mb-4`}/>

            <EditInformationContainer/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <DatabaseEditContainer/>
        </Context.Provider>
    );
};
