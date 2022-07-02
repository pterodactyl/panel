import React from 'react';
import tw from 'twin.macro';
import { Route } from 'react-router';
import styled from 'styled-components/macro';
import Fade from '@/components/elements/Fade';
import { SwitchTransition } from 'react-transition-group';

const StyledSwitchTransition = styled(SwitchTransition)`
    ${tw`relative`};

    & section {
        ${tw`absolute w-full top-0 left-0`};
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
