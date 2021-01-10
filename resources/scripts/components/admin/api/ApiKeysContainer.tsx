import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import NewApiKeyButton from '@/components/admin/api/NewApiKeyButton';

export default () => {
    return (
        <AdminContentBlock title={'API Keys'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>API Keys</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Control access credentials for managing this Panel via the API.</p>
                </div>

                <div css={tw`flex ml-auto pl-4`}>
                    <NewApiKeyButton />
                </div>
            </div>
        </AdminContentBlock>
    );
};
