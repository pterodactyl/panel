import tw, { styled } from 'twin.macro';

const Label = styled.label<{ isLight?: boolean, noBottomSpacing?: boolean }>`
    ${tw`block text-xs uppercase text-neutral-200`};
    ${props => !props.noBottomSpacing && tw`mb-1 sm:mb-2`};
    ${props => props.isLight && tw`text-neutral-700`};
`;

export default Label;
