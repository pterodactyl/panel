import React, { forwardRef } from 'react';
import { Form } from 'formik';
import styled from 'styled-components';
import { breakpoint } from '@/theme';
import FlashMessageRender from '@/components/FlashMessageRender';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
};

const Container = styled.div`
    ${breakpoint('sm')`
        width: 80%;
        margin-left: auto;
        margin-right: auto;
    `};

    ${breakpoint('md')`
        padding: 2.5rem;
    `};

    ${breakpoint('lg')`
        width: 60%;
    `};

    ${breakpoint('xl')`
        width: 100%;
        max-width: 700px;
    `};
`;

export default forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => (
    <Container>
        {title && <h2 className={'text-3xl text-center text-neutral-100 font-medium py-4'}>{title}</h2>}
        <FlashMessageRender className={'mb-2 px-1'} />
        <Form {...props} ref={ref}>
            <div className={'md:flex w-full bg-white shadow-lg rounded-lg p-6 md:pl-0 mx-1'}>
                <div className={'flex-none select-none mb-6 md:mb-0 self-center'}>
                    <img src={'/assets/svgs/pterodactyl.svg'} className={'block w-48 md:w-64 mx-auto'} />
                </div>
                <div className={'flex-1'}>{props.children}</div>
            </div>
        </Form>
        <p className={'text-center text-neutral-500 text-xs mt-4'}>
            &copy; 2015 - {new Date().getFullYear()}&nbsp;
            <a
                rel={'noopener nofollow noreferrer'}
                href={'https://pterodactyl.io'}
                target={'_blank'}
                className={'no-underline text-neutral-500 hover:text-neutral-300'}
            >
                Pterodactyl Software
            </a>
        </p>
    </Container>
));
