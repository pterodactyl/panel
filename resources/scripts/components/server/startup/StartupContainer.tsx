import React from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import useServer from '@/plugins/useServer';
import tw from 'twin.macro';
import VariableBox from '@/components/server/startup/VariableBox';
import ServerContentBlock from '@/components/elements/ServerContentBlock';

const StartupContainer = () => {
    const { invocation, variables } = useServer();

    return (
        <ServerContentBlock title={'Startup Settings'}>
            <TitledGreyBox title={'Startup Command'}>
                <div css={tw`px-1 py-2`}>
                    <p css={tw`font-mono bg-neutral-900 rounded py-2 px-4`}>
                        {invocation}
                    </p>
                </div>
            </TitledGreyBox>
            <div css={tw`grid gap-8 grid-cols-2 mt-10`}>
                {variables.map(variable => <VariableBox key={variable.envVariable} variable={variable}/>)}
            </div>
        </ServerContentBlock>
    );
};

export default StartupContainer;
