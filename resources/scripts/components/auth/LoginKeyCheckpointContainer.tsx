import React from 'react';
import tw from 'twin.macro';
import { DivContainer as LoginFormContainer } from '@/components/auth/LoginFormContainer';

const LoginKeyCheckpointContainer = () => {
    return (
        <LoginFormContainer title={'Login to Continue'} css={tw`w-full flex`} />
    );
};

export default LoginKeyCheckpointContainer;
