import React from 'react';
import { Route } from 'react-router';
import { SwitchTransition } from 'react-transition-group';
import Fade from '@/components/elements/Fade';
import styled from 'styled-components/macro';

const StyledSwitchTransition = styled(SwitchTransition)`
    position: relative;

    & section {
        position: absolute;
        width: 100%;
        top: 0px;
        left: 0px;
    }
`;

const TransitionRouter: React.FC = ({ children }) => {
    return (
        <Route
            render={({ location }) => (
                <StyledSwitchTransition>
                    <Fade timeout={150} key={location.pathname + location.search} in appear unmountOnExit>
                        <section>{children}</section>
                    </Fade>
                </StyledSwitchTransition>
            )}
        />
    );
};

export default TransitionRouter;
