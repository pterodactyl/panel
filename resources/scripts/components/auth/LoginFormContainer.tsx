import React, { forwardRef } from 'react';
import { Form } from 'formik';
import { breakpoint } from '@/theme';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw, { styled } from 'twin.macro';

const Wrapper = styled.div`
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

const Inner = ({ children }: { children: React.ReactNode }) => (
    <div css={tw`md:flex w-full bg-white shadow-lg rounded-lg p-6 md:pl-0 mx-1`}>
        <div css={tw`flex-none select-none mb-6 md:mb-0 self-center`}>
            <img src={'/assets/svgs/pterodactyl.svg'} css={tw`block w-48 md:w-64 mx-auto`}/>
        </div>
        <div css={tw`flex-1`}>
            {children}
        </div>
    </div>
);

const Container = ({ title, children }: { title?: string, children: React.ReactNode }) => (
    <Wrapper>
        {title &&
        <h2 css={tw`text-3xl text-center text-neutral-100 font-medium py-4`}>
            {title}
        </h2>
        }
        <FlashMessageRender css={tw`mb-2 px-1`}/>
        {children}
        <p css={tw`text-center text-neutral-500 text-xs mt-4`}>
            &copy; 2015 - {(new Date()).getFullYear()}&nbsp;
            <a
                rel={'noopener nofollow noreferrer'}
                href={'https://pterodactyl.io'}
                target={'_blank'}
                css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
            >
                Pterodactyl Software
            </a>
        </p>
    </Wrapper>
);

type FormContainerProps = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
}

const FormContainer = forwardRef<HTMLFormElement, FormContainerProps>(({ title, ...props }, ref) => (
    <Container title={title}>
        <Form {...props} ref={ref}>
            <Inner>{props.children}</Inner>
        </Form>
    </Container>
));

type DivContainerProps = React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> & {
    title?: string;
}

export const DivContainer = ({ title, ...props }: DivContainerProps) => (
    <Container title={title}>
        <div {...props}>
            <Inner>{props.children}</Inner>
        </div>
    </Container>
);

export default FormContainer;
