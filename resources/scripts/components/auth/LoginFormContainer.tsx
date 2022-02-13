import React, { forwardRef } from 'react';
import { Form } from 'formik';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw, { styled } from 'twin.macro';
import PterodactylLogo from '@/assets/images/pterodactyl.svg';
import { Link } from 'react-router-dom';

const Wrapper = styled.div`
  ${tw`sm:w-4/5 sm:mx-auto md:p-10 lg:w-3/5 xl:w-full`}
  max-width: 700px;
`;

interface InnerContentProps {
    children: React.ReactNode;
    sidebar?: React.ReactNode;
}

const InnerContainer = ({ children, sidebar }: InnerContentProps) => (
    <div css={tw`md:flex w-full bg-white shadow-lg rounded-lg p-6 md:pl-0 mx-1`}>
        <div css={tw`flex-none select-none mb-6 md:mb-0 self-center w-48 md:w-64 mx-auto`}>
            {sidebar || <Link to={'/auth/login'}><img src={PterodactylLogo} css={tw`block w-full`}/></Link>}
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
    sidebar?: React.ReactNode;
}

const FormContainer = forwardRef<HTMLFormElement, FormContainerProps>(({ title, sidebar, ...props }, ref) => (
    <Container title={title}>
        <Form {...props} ref={ref}>
            <InnerContainer sidebar={sidebar}>{props.children}</InnerContainer>
        </Form>
    </Container>
));

type DivContainerProps = React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> & {
    title?: string;
    sidebar?: React.ReactNode;
}

export const DivContainer = ({ title, sidebar, ...props }: DivContainerProps) => (
    <Container title={title}>
        <div {...props}>
            <InnerContainer sidebar={sidebar}>{props.children}</InnerContainer>
        </div>
    </Container>
);

export default FormContainer;
