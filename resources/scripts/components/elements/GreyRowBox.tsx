import styled from 'styled-components/macro';
import tw from 'twin.macro';

export default styled.div`
    ${tw`flex rounded no-underline text-neutral-200 items-center bg-neutral-700 p-4 border border-transparent transition-colors duration-150`};
    
    &:not(.no-hover):hover {
        ${tw`border-neutral-500`};
    }

    & > div.icon {
        ${tw`rounded-full bg-neutral-500 p-3`};
    }
`;
