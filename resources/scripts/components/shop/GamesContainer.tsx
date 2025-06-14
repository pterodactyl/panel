import React, { useEffect } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useSWR from 'swr';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Spinner from '@/components/elements/Spinner';
import getGames from '@/api/shop/getGames';
import { useParams } from 'react-router';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import OrderButton from '@/components/shop/OrderButton';

export interface GamesResponse {
    category: any;
    games: any[];
    balance: number;
    tos: any[];
    currency: string;
}

export default () => {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    const { category } = useParams();

    const { data, error, mutate } = useSWR<GamesResponse>([category, '/games'], (category) => getGames(category));

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        if (!error) {
            clearFlashes('shop');
        } else {
            clearAndAddHttpError({ key: 'shop', error });
        }
    }, [error]);

    return (
        <PageContentBlock title={'Shop Games'} showFlashKey={'shop'}>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    <div css={tw`w-full flex flex-wrap`}>
                        {data.games.map((item, key) => (
                            <div css={tw`w-full md:w-4/12 md:pl-2 md:pr-2 pt-4`} key={key}>
                                <TitledGreyBox title={item.name}>
                                    <div css={tw`px-1 py-2`}>
                                        <div css={tw`flex flex-wrap`}>
                                            <div css={tw`w-auto`}>
                                                <img css={'width: 100%;'} src={item.image_url} />
                                            </div>
                                        </div>
                                        <div css={tw`flex items-center justify-between mt-2 text-sm`}>
                                            <p>Memory</p>
                                            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{item.memory} MB</code>
                                        </div>
                                        <div css={tw`flex items-center justify-between mt-2 text-sm`}>
                                            <p>Swap</p>
                                            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{item.swap} MB</code>
                                        </div>
                                        <div css={tw`flex items-center justify-between mt-2 text-sm`}>
                                            <p>Disk</p>
                                            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{item.disk} MB</code>
                                        </div>
                                        <div css={tw`flex items-center justify-between mt-2 text-sm pb-3`}>
                                            <p>Database Count</p>
                                            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>Maximum {item.database_limit}</code>
                                        </div>
                                        <hr />
                                        <div css={tw`flex items-center justify-between mt-2 text-sm pb-3`}>
                                            <p>My Balance</p>
                                            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                {data.balance} {data.currency}
                                            </code>
                                        </div>
                                        <div css={tw`text-center`}>
                                            <OrderButton tos={data.tos} game={item} currency={data.currency} onBuy={() => mutate()} />
                                        </div>
                                    </div>
                                </TitledGreyBox>
                            </div>
                        ))}
                    </div>
                </>
            )}
        </PageContentBlock>
    );
};
