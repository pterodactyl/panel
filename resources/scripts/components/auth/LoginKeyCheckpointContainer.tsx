import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import webauthnChallenge from '@/api/account/webauthn/webauthnChallenge';
import { DivContainer as LoginFormContainer } from '@/components/auth/LoginFormContainer';
import useFlash from '@/plugins/useFlash';
import { useLocation } from 'react-router';
import { Link, useHistory } from 'react-router-dom';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';

interface LocationParams {
    token: string;
    publicKey: any;
    hasTotp: boolean;
}

const LoginKeyCheckpointContainer = () => {
    const history = useHistory();
    const location = useLocation<LocationParams>();

    const { clearAndAddHttpError } = useFlash();

    const [ challenging, setChallenging ] = useState(false);

    const switchToCode = () => {
        history.replace('/auth/login/checkpoint', { ...location.state, recovery: false });
    };

    const switchToRecovery = () => {
        history.replace('/auth/login/checkpoint', { ...location.state, recovery: true });
    };

    const doChallenge = () => {
        setChallenging(true);

        webauthnChallenge(location.state.token, location.state.publicKey)
            .then(response => {
                if (!response.complete) {
                    return;
                }

                // @ts-ignore
                window.location = response.intended || '/';
            })
            .catch(error => {
                clearAndAddHttpError({ error });
                console.error(error);
                setChallenging(false);
            });
    };

    useEffect(() => {
        doChallenge();
    }, []);

    return (
        <LoginFormContainer title={'Key Checkpoint'} css={tw`w-full flex`}>
            <div css={tw`flex flex-col items-center justify-center w-full md:h-full md:pt-4`}>
                <h3 css={tw`font-sans text-2xl text-center text-neutral-500 font-normal`}>Attempting challenge...</h3>

                <div css={tw`mt-6 md:mt-auto`}>
                    {challenging ?
                        <Spinner size={'large'} isBlue/>
                        :
                        <Button onClick={() => doChallenge()}>
                            Retry
                        </Button>
                    }
                </div>

                <div css={tw`flex flex-row text-center mt-6 md:mt-auto`}>
                    <div css={tw`mr-4`}>
                        <a
                            css={tw`text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700 text-center cursor-pointer`}
                            onClick={() => switchToCode()}
                        >
                            Use two-factor token
                        </a>
                    </div>
                    <div css={tw`ml-4`}>
                        <a
                            css={tw`text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700 text-center cursor-pointer`}
                            onClick={() => switchToRecovery()}
                        >
                            I&apos;ve Lost My Device
                        </a>
                    </div>
                </div>
                <div css={tw`mt-6 text-center`}>
                    <Link
                        to={'/auth/login'}
                        css={tw`text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700`}
                    >
                        Return to Login
                    </Link>
                </div>
            </div>
        </LoginFormContainer>
    );
};

export default () => {
    const history = useHistory();
    const location = useLocation<LocationParams>();

    if (!location.state?.token) {
        history.replace('/auth/login');
        return null;
    }

    return <LoginKeyCheckpointContainer/>;
};
