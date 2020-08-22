import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Button from '@/components/elements/Button';

export default () => {
    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Databases</h2>
                    <p css={tw`text-base text-neutral-400`}>Database hosts that servers can have databases created on.</p>
                </div>

                <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`}>
                    New Database
                </Button>
            </div>
        </AdminContentBlock>
    );
};
