import React, { forwardRef } from 'react';
import styled from 'styled-components';
import { Form } from 'formik';
import { breakpoint } from 'styled-components-breakpoint';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement>;

const LoginContainer = styled.div`
    ${tw`bg-white shadow-lg rounded-lg p-6 mx-1`};

    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`flex p-10`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 660px;
    `};
`;

export default forwardRef<any, Props>(({ className, ...props }, ref) => (
    <Form {...props}>
        <LoginContainer>
            <div className={'flex-none select-none mb-6 md:mb-0 self-center'}>
                <img src={'/assets/pterodactyl.svg'} className={'block w-48 md:w-64 mx-auto'}/>
            </div>
            <div className={'flex-1'}>
                {props.children}
            </div>
        </LoginContainer>
    </Form>
));
