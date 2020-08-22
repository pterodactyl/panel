import React from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import useServer from '@/plugins/useServer';
import tw from 'twin.macro';

const StartupContainer = () => {
    const { invocation } = useServer();

    return (
        <PageContentBlock title={'Startup Settings'} showFlashKey={'server:startup'}>
            <TitledGreyBox title={'Startup Command'}>
                <div css={tw`px-1 py-2`}>
                    <p css={tw`font-mono bg-neutral-900 rounded py-2 px-4`}>
                        {invocation}
                    </p>
                </div>
            </TitledGreyBox>
        </PageContentBlock>
    );
};

export default StartupContainer;
