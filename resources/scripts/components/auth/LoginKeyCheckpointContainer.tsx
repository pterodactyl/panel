import React, { useEffect, useRef, useState } from 'react';
import tw from 'twin.macro';
import { DivContainer as LoginFormContainer } from '@/components/auth/LoginFormContainer';
import useFlash from '@/plugins/useFlash';
import { useLocation } from 'react-router';
import { Link, useHistory } from 'react-router-dom';
import Button from '@/components/elements/Button';
import { authenticateSecurityKey } from '@/api/account/security-keys';
import { base64Decode, bufferDecode, bufferEncode, decodeSecurityKeyCredentials } from '@/helpers';
import { FingerPrintIcon } from '@heroicons/react/outline';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface LocationParams {
    token: string;
    publicKey: any;
    hasTotp: boolean;
}

interface Credential extends PublicKeyCredential {
    response: AuthenticatorAssertionResponse;
}

const challenge = async (publicKey: PublicKeyCredentialRequestOptions, signal?: AbortSignal): Promise<Credential> => {
    const publicKeyCredential = Object.assign({}, publicKey);

    publicKeyCredential.challenge = bufferDecode(base64Decode(publicKey.challenge.toString()));
    if (publicKey.allowCredentials) {
        publicKeyCredential.allowCredentials = decodeSecurityKeyCredentials(publicKey.allowCredentials);
    }

    const credential = await navigator.credentials.get({ signal, publicKey: publicKeyCredential }) as Credential | null;
    if (!credential) return Promise.reject(new Error('No credentials provided for challenge.'));

    return credential;
};

export default () => {
    const history = useHistory();
    const location = useLocation<LocationParams>();
    const controller = useRef(new AbortController());
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ redirecting, setRedirecting ] = useState(false);

    const triggerChallengePrompt = () => {
        clearFlashes();

        challenge(location.state.publicKey, controller.current.signal)
            .then((credential) => {
                setRedirecting(true);

                return authenticateSecurityKey({
                    confirmation_token: location.state.token,
                    data: JSON.stringify({
                        id: credential.id,
                        type: credential.type,
                        rawId: bufferEncode(credential.rawId),
                        response: {
                            authenticatorData: bufferEncode(credential.response.authenticatorData),
                            clientDataJSON: bufferEncode(credential.response.clientDataJSON),
                            signature: bufferEncode(credential.response.signature),
                            userHandle: credential.response.userHandle ? bufferEncode(credential.response.userHandle) : null,
                        },
                    }),
                });
            })
            .then(({ complete, intended }) => {
                if (!complete) return;

                // @ts-ignore
                window.location = intended || '/';
            })
            .catch(error => {
                setRedirecting(false);
                if (error instanceof DOMException) {
                    // User canceled the operation.
                    if (error.code === 20) {
                        return;
                    }
                }
                clearAndAddHttpError({ error });
            });
    };

    useEffect(() => {
        return () => {
            controller.current.abort();
        };
    });

    useEffect(() => {
        if (!location.state?.token) {
            history.replace('/auth/login');
        } else {
            triggerChallengePrompt();
        }
    }, []);

    return (
        <LoginFormContainer
            title={'Two-Factor Authentication'}
            css={tw`w-full flex`}
            sidebar={<FingerPrintIcon css={tw`h-24 w-24 mx-auto animate-pulse`}/>}
        >
            <SpinnerOverlay size={'base'} visible={redirecting}/>
            <div css={tw`flex flex-col md:h-full`}>
                <div css={tw`flex-1`}>
                    <p css={tw`text-neutral-700`}>Insert your security key and touch it.</p>
                    <p css={tw`text-neutral-700 mt-2`}>
                        If your security key does not respond,&nbsp;
                        <a
                            href={'#'}
                            css={tw`text-primary-500 font-medium hover:underline`}
                            onClick={triggerChallengePrompt}
                        >
                            click here
                        </a>.
                    </p>
                </div>
                <Link
                    css={tw`block mt-12 mb-6`}
                    to={{ pathname: '/auth/login/checkpoint', state: location.state }}
                >
                    <Button size={'small'} type={'button'} css={tw`block w-full`}>
                        Use a Different Method
                    </Button>
                </Link>
                <Link
                    to={{ pathname: '/auth/login/checkpoint', state: { token: location.state.token, recovery: true } }}
                    css={tw`text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700 text-center cursor-pointer`}
                >
                    {'I\'ve Lost My Device'}
                </Link>
            </div>
        </LoginFormContainer>
    );
};
