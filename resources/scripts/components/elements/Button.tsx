import React from 'react';
import styled, { css } from 'styled-components/macro';
import tw from 'twin.macro';

interface Props {
    isLoading?: boolean;
    size?: 'xsmall' | 'small' | 'large' | 'xlarge';
    color?: 'green' | 'red' | 'primary' | 'grey';
    isSecondary?: boolean;
    disabled?: boolean;
}

const StyledButton = styled.button<Props>`
    ${tw`rounded p-2 uppercase tracking-wide text-sm transition-all duration-150`};
    
        ${props => props.isSecondary && css<Props>`
        ${tw`border border-neutral-600 bg-transparent text-neutral-200`};
        
        &:hover:not(:disabled) {
            ${tw`border-neutral-500 text-neutral-100`};
            ${props => props.color === 'red' && tw`bg-red-500 border-red-600 text-red-50`};
            ${props => props.color === 'green' && tw`bg-green-500 border-green-600 text-green-50`};
        }
    `};
    
    ${props => (!props.color || props.color === 'primary') && css<Props>`
        ${props => !props.isSecondary && tw`bg-primary-500 border-primary-600 border text-primary-50`};
        
        &:hover:not(:disabled) {
            ${tw`bg-primary-600 border-primary-700`};
        }
    `};
    
    ${props => props.color === 'grey' && css`
        ${tw`border border-neutral-600 bg-neutral-500 text-neutral-50`};
        
        &:hover:not(:disabled) {
            ${tw`bg-neutral-600 border-neutral-700`};
        }
    `};
    
    ${props => props.color === 'green' && css<Props>`
        ${tw`border border-green-600 bg-green-500 text-green-50`};
        
        &:hover:not(:disabled) {
            ${tw`bg-green-600 border-green-700`};
        }
        
        ${props => props.isSecondary && css`
            &:active:not(:disabled) {
                ${tw`bg-green-600 border-green-700`};
            }
        `};
    `};
    
    ${props => props.color === 'red' && css<Props>`
        ${tw`border border-red-600 bg-red-500 text-red-50`};
        
        &:hover:not(:disabled) {
            ${tw`bg-red-600 border-red-700`};
        }
        
        ${props => props.isSecondary && css`
            &:active:not(:disabled) {
                ${tw`bg-red-600 border-red-700`};
            }
        `};
    `};
    
    ${props => props.size === 'xsmall' && tw`p-2 text-xs`};
    ${props => (!props.size || props.size === 'small') && tw`p-3`};
    ${props => props.size === 'large' && tw`p-4 text-sm`};
    ${props => props.size === 'xlarge' && tw`p-4 w-full`};
    
    &:disabled { opacity: 0.55; cursor: default }
    
    ${props => props.disabled && css`opacity: 0.55; cursor: default`};

`;

type ComponentProps = Props &
    Omit<React.DetailedHTMLProps<React.ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement>, keyof Props>;

const Button: React.FC<ComponentProps> = ({ children, isLoading, ...props }) => (
    <StyledButton {...props}>
        {isLoading &&
        <div css={tw`w-full flex absolute justify-center`} style={{ marginLeft: '-0.75rem' }}>
            <div className={'spinner-circle spinner-white spinner-sm'}/>
        </div>
        }
        <span css={isLoading && tw`text-transparent`}>
            {children}
        </span>
    </StyledButton>
);

export default Button;
