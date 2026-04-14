import React from 'react';
import styled from 'styled-components';
import CSSTransition, { CSSTransitionProps } from 'react-transition-group/CSSTransition';

interface Props extends Omit<CSSTransitionProps, 'timeout' | 'classNames'> {
    timeout: number;
}

const Container = styled.div<{ timeout: number }>`
    .fade-enter,
    .fade-exit,
    .fade-appear {
        will-change: opacity;
    }

    .fade-enter,
    .fade-appear {
        opacity: 0;

        &.fade-enter-active,
        &.fade-appear-active {
            opacity: 1;
            transition-property: opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 1, 1);
            transition-duration: ${(props) => props.timeout}ms;
        }
    }

    .fade-exit {
        opacity: 1;

        &.fade-exit-active {
            opacity: 0;
            transition-property: opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 1, 1);
            transition-duration: ${(props) => props.timeout}ms;
        }
    }
`;

const Fade: React.FC<Props> = ({ timeout, children, ...props }) => (
    <Container timeout={timeout}>
        <CSSTransition timeout={timeout} classNames={'fade'} {...props}>
            {children}
        </CSSTransition>
    </Container>
);
Fade.displayName = 'Fade';

export default Fade;
