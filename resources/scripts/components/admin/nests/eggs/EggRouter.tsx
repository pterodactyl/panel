import { useEffect } from 'react';
import { Route, Routes, useParams } from 'react-router-dom';
import tw from 'twin.macro';

import { useEggFromRoute } from '@/api/admin/egg';
import EggInstallContainer from '@/components/admin/nests/eggs/EggInstallContainer';
import EggVariablesContainer from '@/components/admin/nests/eggs/EggVariablesContainer';
import useFlash from '@/plugins/useFlash';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import EggSettingsContainer from '@/components/admin/nests/eggs/EggSettingsContainer';

const EggRouter = () => {
    const { id, nestId } = useParams<'nestId' | 'id'>();

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: egg, error, isValidating, mutate } = useEggFromRoute();

    useEffect(() => {
        void mutate();
    }, []);

    useEffect(() => {
        if (!error) clearFlashes('egg');
        if (error) clearAndAddHttpError({ key: 'egg', error });
    }, [error]);

    if (!egg || (error && isValidating)) {
        return (
            <AdminContentBlock showFlashKey={'egg'}>
                <Spinner size={'large'} centered />
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Egg - ' + egg.name}>
            <div css={tw`w-full flex flex-row items-center mb-4`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{egg.name}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        {egg.uuid}
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'egg'} css={tw`mb-4`} />

            <SubNavigation>
                <SubNavigationLink to={`/admin/nests/${nestId ?? ''}/eggs/${id ?? ''}`} name={'About'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z"
                        />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`/admin/nests/${nestId ?? ''}/eggs/${id ?? ''}/variables`} name={'Variables'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z" />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`/admin/nests/${nestId ?? ''}/eggs/${id ?? ''}/install`} name={'Install Script'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            clipRule="evenodd"
                            fillRule="evenodd"
                            d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z"
                        />
                    </svg>
                </SubNavigationLink>
            </SubNavigation>

            <Routes>
                <Route path="" element={<EggSettingsContainer />} />
                <Route path="variables" element={<EggVariablesContainer />} />
                <Route path="install" element={<EggInstallContainer />} />
            </Routes>
        </AdminContentBlock>
    );
};

export default () => {
    return <EggRouter />;
};
