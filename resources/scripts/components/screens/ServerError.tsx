import React from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';

interface Props {
    title?: string;
    message: string;
}

export default ({ title, message }: Props) => (
    <PageContentBlock>
        <div className={'flex justify-center'}>
            <div className={'w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-neutral-100 rounded-lg shadow-lg text-center'}>
                <img src={'/assets/svgs/server_error.svg'} className={'w-2/3 h-auto select-none'}/>
                <h2 className={'mt-6 text-neutral-900 font-bold'}>{ title || 'Something went wrong!' }</h2>
                <p className={'text-sm text-neutral-700 mt-2'}>
                    {message}
                </p>
            </div>
        </div>
    </PageContentBlock>
);
