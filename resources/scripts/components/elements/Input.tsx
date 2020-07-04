import React from 'react';
import styled, { css } from 'styled-components/macro';
import tw from 'twin.macro';

export interface Props {
    isLight?: boolean;
    hasError?: boolean;
}

const light = css<Props>`
    ${tw`bg-white border-neutral-200 text-neutral-800`};    
    &:focus { ${tw`border-primary-400`} }
        
    &:disabled {
        ${tw`bg-neutral-100 border-neutral-200`};
    }
`;

const Input = styled.input<Props>`
    // Reset to normal styling.
    ${tw`appearance-none w-full min-w-0`};
    ${tw`p-3 border rounded text-sm transition-all duration-150`};
    ${tw`bg-neutral-600 border-neutral-500 hover:border-neutral-400 text-neutral-200 shadow-none`};
    
    ${props => props.hasError && tw`text-red-600 border-red-500 hover:border-red-600`};
    & + .input-help {
        ${tw`mt-1 text-xs`};
        ${props => props.hasError ? tw`text-red-400` : tw`text-neutral-400`};
    }
    
    &:required, &:invalid {
        ${tw`shadow-none`};
    }
    
    &:focus {
        ${tw`shadow-md border-neutral-400`};
    }

    &:disabled {
        ${tw`opacity-75`};
    }
    
    ${props => props.isLight && light};
`;

export default Input;
