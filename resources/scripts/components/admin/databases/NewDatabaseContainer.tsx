import React from 'react';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import createDatabase from '@/api/admin/databases/createDatabase';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import { FormikHelpers } from 'formik';
import FlashMessageRender from '@/components/FlashMessageRender';
import { InformationContainer, Values } from '@/components/admin/databases/DatabaseEditContainer';

export default () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = ({ name, host, port, username, password }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('database:create');

        createDatabase(name, host, port, username, password)
            .then(database => history.push(`/admin/databases/${database.id}`))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'database:create', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={'New Database'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>New Database Host</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Add a new database host to the panel.</p>
                </div>
            </div>

            <FlashMessageRender byKey={'database:create'} css={tw`mb-4`}/>

            <InformationContainer title={'Create Database'} onSubmit={submit}/>
        </AdminContentBlock>
    );
};
