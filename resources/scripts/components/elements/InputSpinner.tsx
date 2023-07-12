import type { ReactNode } from 'react';
import styled, { css } from 'styled-components';
import tw from 'twin.macro';

import Select from '@/components/elements/Select';
import Spinner from '@/components/elements/Spinner';
import FadeTransition from '@/components/elements/transitions/FadeTransition';

const Container = styled.div<{ visible?: boolean }>`
    ${tw`relative`};

    ${props =>
        props.visible &&
        css`
            & ${Select} {
                background-image: none;
            }
        `};
`;

function InputSpinner({ visible, children }: { visible: boolean; children: ReactNode }) {
    return (
        <Container visible={visible}>
            <FadeTransition show={visible} duration="duration-150" appear unmount>
                <div css={tw`absolute right-0 h-full flex items-center justify-end pr-3`}>
                    <Spinner size="small" />
                </div>
            </FadeTransition>

            {children}
        </Container>
    );
}

export default InputSpinner;
