import React from 'react';
import styled, { css } from 'styled-components';
import Spinner from '@/components/elements/Spinner';

interface Props {
    isLoading?: boolean;
    size?: 'xsmall' | 'small' | 'large' | 'xlarge';
    color?: 'green' | 'red' | 'primary' | 'grey';
    isSecondary?: boolean;
}

const ButtonStyle = styled.button<Omit<Props, 'isLoading'>>`
    position: relative;
    display: inline-block;
    border-radius: 0.25rem;
    padding: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
    border-width: 1px;

    ${(props) =>
        ((!props.isSecondary && !props.color) || props.color === 'primary') &&
        css<Props>`
            ${(props) =>
                !props.isSecondary &&
                `
                background-color: #3b82f6;
                border-color: #2563eb;
                border-width: 1px;
                color: #eff6ff;
            `};

            &:hover:not(:disabled) {
                background-color: #2563eb;
                border-color: #1d4ed8;
            }
        `};

    ${(props) =>
        props.color === 'grey' &&
        css`
            border-color: hsl(209, 14%, 37%);
            background-color: hsl(211, 12%, 43%);
            color: hsl(216, 33%, 97%);

            &:hover:not(:disabled) {
                background-color: hsl(209, 14%, 37%);
                border-color: hsl(209, 18%, 30%);
            }
        `};

    ${(props) =>
        props.color === 'green' &&
        css<Props>`
            border-color: #16a34a;
            background-color: #22c55e;
            color: #f0fdf4;

            &:hover:not(:disabled) {
                background-color: #16a34a;
                border-color: #15803d;
            }

            ${(props) =>
                props.isSecondary &&
                css`
                    &:active:not(:disabled) {
                        background-color: #16a34a;
                        border-color: #15803d;
                    }
                `};
        `};

    ${(props) =>
        props.color === 'red' &&
        css<Props>`
            border-color: #dc2626;
            background-color: #ef4444;
            color: #fef2f2;

            &:hover:not(:disabled) {
                background-color: #dc2626;
                border-color: #b91c1c;
            }

            ${(props) =>
                props.isSecondary &&
                css`
                    &:active:not(:disabled) {
                        background-color: #dc2626;
                        border-color: #b91c1c;
                    }
                `};
        `};

    ${(props) =>
        props.size === 'xsmall' &&
        `
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
        font-size: 0.75rem;
        line-height: 1rem;
    `};
    ${(props) =>
        (!props.size || props.size === 'small') &&
        `
        padding-left: 1rem;
        padding-right: 1rem;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    `};
    ${(props) =>
        props.size === 'large' &&
        `
        padding: 1rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
    `};
    ${(props) =>
        props.size === 'xlarge' &&
        `
        padding: 1rem;
        width: 100%;
    `};

    ${(props) =>
        props.isSecondary &&
        css<Props>`
            border-color: hsl(209, 14%, 37%);
            background-color: transparent;
            color: hsl(210, 16%, 82%);

            &:hover:not(:disabled) {
                border-color: hsl(211, 12%, 43%);
                color: hsl(214, 15%, 91%);
                ${(props) =>
                    props.color === 'red' && `background-color: #ef4444; border-color: #dc2626; color: #fef2f2;`};
                ${(props) =>
                    props.color === 'primary' && `background-color: #3b82f6; border-color: #2563eb; color: #eff6ff;`};
                ${(props) =>
                    props.color === 'green' && `background-color: #22c55e; border-color: #16a34a; color: #f0fdf4;`};
            }
        `};

    &:disabled {
        opacity: 0.55;
        cursor: default;
    }
`;

type ComponentProps = Omit<JSX.IntrinsicElements['button'], 'ref' | keyof Props> & Props;

const Button: React.FC<ComponentProps> = ({ children, isLoading, ...props }) => (
    <ButtonStyle {...props}>
        {isLoading && (
            <div className={'flex absolute justify-center items-center w-full h-full left-0 top-0'}>
                <Spinner size={'small'} />
            </div>
        )}
        <span className={isLoading ? 'text-transparent' : undefined}>{children}</span>
    </ButtonStyle>
);

type LinkProps = Omit<JSX.IntrinsicElements['a'], 'ref' | keyof Props> & Props;

const LinkButton: React.FC<LinkProps> = (props) => <ButtonStyle as={'a'} {...props} />;

export { LinkButton, ButtonStyle };
export default Button;
