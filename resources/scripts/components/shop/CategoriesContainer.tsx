import React, { useEffect } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import useSWR from 'swr';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Spinner from '@/components/elements/Spinner';
import getCategories from '@/api/shop/getCategories';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Button from '@/components/elements/Button';
import { Link } from 'react-router-dom';

export interface CategoriesResponse {
    categories: any[];
}

export default () => {
    const { data, error } = useSWR<CategoriesResponse>([ '/games' ], () => getCategories());

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        if (!error) {
            clearFlashes('shop');
        } else {
            clearAndAddHttpError({ key: 'shop', error });
        }
    }, [ error ]);

    return (
        <PageContentBlock title={'Shop Categories'} showFlashKey={'shop'}>
            {!data ?
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
                :
                <>
                    <div css={tw`w-full flex flex-wrap`}>
                        {data.categories.map((item, key) => (
                            <div css={tw`w-full md:w-4/12 md:pl-2 md:pr-2 pt-4`} key={key}>
                                <TitledGreyBox title={item.title}>
                                    <div css={tw`px-1 py-2`}>
                                        <div css={tw`flex flex-wrap`}>
                                            <div css={tw`w-auto`}>
                                                <img css={'width: 100%;'} src={item.image_url} />
                                            </div>
                                        </div>
                                        <div css={tw`text-center`}>
                                            <Link to={`/shop/${item.short_url}`}>
                                                <Button color={'primary'} css={tw`mt-3`}>
                                                    View Category
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </TitledGreyBox>
                            </div>
                        ))}
                    </div>
                </>
            }
        </PageContentBlock>
    );
};
