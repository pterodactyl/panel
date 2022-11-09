import React from 'react';
import { useStoreState } from 'easy-peasy';
import Button from '@/components/elements/button/Button';
import useWindowDimensions from '@/plugins/useWindowDimensions';
import ResourceBar from '@/components/elements/store/ResourceBar';
import PageContentBlock from '@/components/elements/PageContentBlock';

export default () => {
    const { width } = useWindowDimensions();
    const username = useStoreState((state) => state.user.data!.username);

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <div className={'flex flex-row items-center justify-between mt-10'}>
                {width >= 1280 && (
                    <div>
                        <h1 className={'j-left text-6xl'}>Hey, {username}!</h1>
                        <h3 className={'j-left text-2xl mt-2 text-neutral-500'}>👋 Welcome to the store.</h3>
                    </div>
                )}
                <ResourceBar className={'w-full lg:w-3/4'} />
            </div>
            <div className={'lg:grid lg:grid-cols-3 gap-8 my-10'}>
                <div className={'w-full bg-auto bg-center rounded-tr-xl rounded-bl-xl bg-storeone'}>
                    <div className={'bg-gray-900 bg-opacity-75 text-center rounded-lg p-2 m-2 lg:mt-[40rem]'}>
                        <p className={'text-3xl text-gray-200'}>Want to create a server?</p>
                        <Button className={'my-2 w-full lg:w-1/2'}>Create</Button>
                    </div>
                </div>
                <div className={'w-full bg-auto bg-center rounded-tr-xl rounded-bl-xl bg-storetwo'}>
                    <div className={'bg-gray-900 bg-opacity-75 text-center rounded-lg p-2 m-2 lg:mt-[40rem]'}>
                        <p className={'text-3xl text-gray-200'}>Need more resources?</p>
                        <Button className={'my-2 w-full lg:w-1/2'}>Purchase</Button>
                    </div>
                </div>
                <div className={'w-full bg-auto bg-center rounded-tr-xl rounded-bl-xl bg-storethree'}>
                    <div className={'bg-gray-900 bg-opacity-75 text-center rounded-lg p-2 m-2 lg:mt-[40rem]'}>
                        <p className={'text-3xl text-gray-200'}>Sample text here</p>
                        <Button className={'my-2 w-full lg:w-1/2'}>Modify</Button>
                    </div>
                </div>
            </div>
        </PageContentBlock>
    );
};
