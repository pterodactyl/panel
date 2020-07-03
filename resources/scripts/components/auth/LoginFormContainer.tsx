import React, { forwardRef } from 'react';
import { Form } from 'formik';
import styled from 'styled-components/macro';
import { breakpoint } from 'styled-components-breakpoint';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
}

const Container = styled.div`
    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`p-10`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 700px;
    `};
`;

export default forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => (
    <Container>
        {title && <h2 className={'text-center text-neutral-100 font-medium py-4'}>
            {title}
        </h2>}
        <FlashMessageRender className={'mb-2 px-1'}/>
        <Form {...props} ref={ref}>
            <div className={'md:flex w-full bg-white shadow-lg rounded-lg p-6 md:pl-0 mx-1'}>
                <div className={'flex-none select-none mb-6 md:mb-0 self-center'}>
                    <img src={'/assets/svgs/pterodactyl.svg'} className={'block w-48 md:w-64 mx-auto'}/>
                </div>
                <div className={'flex-1'}>
                    {props.children}
                </div>
            </div>
        </Form>
        <p className={'text-center text-neutral-500 text-xs mt-4'}>
            &copy; 2015 - 2020&nbsp;
            <a
                rel={'noopener nofollow'}
                href={'https://pterodactyl.io'}
                target={'_blank'}
                className={'no-underline text-neutral-500 hover:text-neutral-300'}
            >
                Pterodactyl Software
            </a>
        </p>
    </Container>
));
