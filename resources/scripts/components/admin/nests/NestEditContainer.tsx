import React, { useEffect, useState } from 'react';
import { useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import useFlash from '@/plugins/useFlash';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Nest } from '@/api/admin/nests/getNests';
import getNest from '@/api/admin/nests/getNest';

export default () => {
    const match = useRouteMatch<{ nestId?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const [ nest, setNest ] = useState<Nest | undefined>(undefined);

    useEffect(() => {
        clearFlashes('nest');

        getNest(Number(match.params?.nestId), [ 'eggs' ])
            .then(nest => setNest(nest))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError(error);
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || nest === undefined) {
        return (
            <AdminContentBlock title={'Nests'}>
                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>

                <FlashMessageRender byKey={'nest'} css={tw`mb-4`}/>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Nests - ' + nest.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{nest.name}</h2>
                    <p css={tw`text-base text-neutral-400`}>{nest.description}</p>
                </div>
            </div>

            <p>{JSON.stringify(nest.relations.eggs)}</p>
        </AdminContentBlock>
    );
};
