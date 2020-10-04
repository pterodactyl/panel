import Button from '@/components/elements/Button';
import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

export default () => {
    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Nodes</h2>
                    <p css={tw`text-base text-neutral-400`}>All nodes available on the system.</p>
                </div>

                <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`}>
                    New Node
                </Button>
            </div>
        </AdminContentBlock>
    );
};
