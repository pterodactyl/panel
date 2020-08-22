import React, { useState } from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Button from '@/components/elements/Button';
import Spinner from '@/components/elements/Spinner';

export default () => {
    const [ loading ] = useState<boolean>(false);
    const [ keys ] = useState<any[]>([]);

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>API Keys</h2>
                    <p css={tw`text-base text-neutral-400`}>Control access credentials for managing this Panel via the API.</p>
                </div>

                <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`}>
                    New API Key
                </Button>
            </div>

            <div css={tw`w-full flex flex-col`}>
                <div css={tw`w-full flex flex-col bg-neutral-700 rounded-lg shadow-md`}>
                    { loading ?
                        <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                            <Spinner/>
                        </div>
                        :
                        keys.length < 1 ?
                            <div css={tw`w-full flex flex-col items-center justify-center pb-6 py-2 sm:py-8 md:py-10 px-8`}>
                                <div css={tw`h-64 flex`}>
                                    <img src={'/assets/svgs/not_found.svg'} alt={'No Items'} css={tw`h-full`}/>
                                </div>

                                <p css={tw`text-xl text-neutral-400 text-center font-normal sm:mt-8`}>No items could be found, it&apos;s almost like they are hiding.</p>
                            </div>
                            :
                            null
                    }
                </div>
            </div>
        </AdminContentBlock>
    );
};
