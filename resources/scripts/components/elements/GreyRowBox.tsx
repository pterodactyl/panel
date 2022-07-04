import tw from 'twin.macro';
import styled from 'styled-components/macro';

export default styled.div<{ $hoverable?: boolean }>`
    ${tw`flex rounded no-underline text-neutral-200 items-center bg-neutral-875 p-4 border border-transparent transition-colors duration-150 overflow-hidden`};

    ${(props) => props.$hoverable !== false && tw`hover:border-neutral-700`};

    & .icon {
        ${tw`rounded-full bg-neutral-500 p-3`};
    }
`;
