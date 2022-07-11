import tw from 'twin.macro';
import styled from 'styled-components/macro';

const Label = styled.label<{ isLight?: boolean }>`
    ${tw`block text-sm uppercase text-neutral-200 font-semibold mb-1 sm:mb-2`};
    ${(props) => props.isLight && tw`text-neutral-700`};
`;

export default Label;
