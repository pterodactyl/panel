import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';

export default styled.div`
    ${tw`flex flex-wrap`};

    & > div {
        ${tw`w-full`};

        ${breakpoint('sm')`
            width: calc(50% - 1rem);
        `}

        ${breakpoint('md')`
            ${tw`w-auto flex-1`};
        `}
    }
`;
