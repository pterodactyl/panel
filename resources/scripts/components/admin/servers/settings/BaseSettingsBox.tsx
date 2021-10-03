import { Server } from '@/api/admin/servers/getServers';
import { useFormikContext } from 'formik';
import AdminBox from '@/components/admin/AdminBox';
import { faCogs } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Field from '@/components/elements/Field';
import OwnerSelect from '@/components/admin/servers/OwnerSelect';
import React from 'react';

export default ({ server }: { server?: Server }) => {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faCogs} title={'Settings'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>
            <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4 md:mb-0`}>
                    <Field
                        id={'name'}
                        name={'name'}
                        label={'Server Name'}
                        type={'text'}
                    />
                </div>

                <div css={tw`mb-6 md:w-full md:flex md:flex-col md:ml-4 md:mb-0`}>
                    <Field
                        id={'externalId'}
                        name={'externalId'}
                        label={'External Identifier'}
                        type={'text'}
                    />
                </div>
            </div>

            <div css={tw`mb-6 md:w-full md:flex md:flex-row`}>
                <div css={tw`mb-6 w-full md:w-1/2 md:flex md:flex-col md:pr-4 md:mb-0`}>
                    <OwnerSelect selected={server?.relations.user || null}/>
                </div>
            </div>
        </AdminBox>
    );
};
