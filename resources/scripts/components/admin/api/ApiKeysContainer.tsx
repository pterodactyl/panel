import NewApiKeyButton from '@/components/admin/api/NewApiKeyButton';
import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';

interface Key {
    id: number,
}

export default () => {
    const [ loading, setLoading ] = useState<boolean>(true);
    const [ keys ] = useState<Key[]>([]);

    useEffect(() => {
        setTimeout(() => {
            setLoading(false);
        }, 500);
    });

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>API Keys</h2>
                    <p css={tw`text-base text-neutral-400`}>Control access credentials for managing this Panel via the API.</p>
                </div>

                <NewApiKeyButton />
            </div>

            <div css={tw`w-full flex flex-col`}>
                <div css={tw`w-full flex flex-col bg-neutral-700 rounded-lg shadow-md`}>
                    { loading ?
                        <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                            <Spinner size={'base'}/>
                        </div>
                        :
                        keys.length < 1 ?
                            <div css={tw`w-full flex flex-col items-center justify-center pb-6 py-2 sm:py-8 md:py-10 px-8`}>
                                <div css={tw`h-64 flex`}>
                                    <img src={'/assets/svgs/not_found.svg'} alt={'No Items'} css={tw`h-full select-none`}/>
                                </div>

                                <p css={tw`text-lg text-neutral-300 text-center font-normal sm:mt-8`}>No items could be found, it&apos;s almost like they are hiding.</p>
                            </div>
                            :
                            null
                    }
                </div>
            </div>
        </AdminContentBlock>
    );
};
