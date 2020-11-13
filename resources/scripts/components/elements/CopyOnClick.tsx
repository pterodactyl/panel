import React, { useCallback, useEffect, useState } from 'react';
import CopyToClipboard from 'react-copy-to-clipboard';
import tw from 'twin.macro';
import styled, { keyframes } from 'styled-components/macro';
import Fade from '@/components/elements/Fade';
import { SwitchTransition } from 'react-transition-group';

const fade = keyframes`
    from { opacity: 0 }
    to { opacity: 1 }
`;

const Toast = styled.div`
    ${tw`fixed z-50 bottom-0 left-0 mb-4 w-full flex justify-end pr-4`};
    animation: ${fade} 250ms linear;

    & > div {
        ${tw`rounded px-4 py-2 text-white bg-neutral-900 border border-black opacity-75`};
    }
`;

const CopyOnClick: React.FC<{ text: any }> = ({ text, children }) => {
    const [ copied, setCopied ] = useState(false);

    useEffect(() => {
        if (!copied) return;

        const timeout = setTimeout(() => {
            setCopied(false);
        }, 2500);

        return () => {
            clearTimeout(timeout);
        };
    }, [ copied ]);

    const onCopy = useCallback(() => {
        setCopied(true);
    }, []);

    return (
        <>
            <SwitchTransition>
                <Fade timeout={250} key={copied ? 'visible' : 'invisible'}>
                    {copied ?
                        <Toast>
                            <div>
                                <p>Copied &quot;{text}&quot; to clipboard.</p>
                            </div>
                        </Toast>
                        :
                        <></>
                    }
                </Fade>
            </SwitchTransition>
            <CopyToClipboard onCopy={onCopy} text={text} options={{ debug: true }} css={tw`cursor-pointer`}>
                {children}
            </CopyToClipboard>
        </>
    );
};

export default CopyOnClick;
