import styled from 'styled-components/macro';
import tw from 'twin.macro';

const Label = styled.label<{ isLight?: boolean }>`
    ${tw`block text-xs uppercase text-neutral-200 mb-1 sm:mb-2`};
    ${(props) => props.isLight && tw`text-neutral-700`};
`;

export default Label;
