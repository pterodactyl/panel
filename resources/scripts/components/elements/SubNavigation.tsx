import tw, { theme } from 'twin.macro';
import styled from 'styled-components/macro';

const SubNavigation = styled.div`
    ${tw`bg-neutral-800 overflow-x-auto font-mono font-semibold mb-4 sm:mb-10 xl:ml-20`};

    & > div {
        ${tw`flex text-sm mx-auto px-2`};

        & > a,
        & > div {
            ${tw`inline-block py-3 px-4 text-neutral-300 no-underline whitespace-nowrap transition-all duration-150`};

            &:not(:first-of-type) {
                ${tw`ml-2`};
            }

            &:hover {
                ${tw`text-neutral-100`};
            }

            &:active,
            &.active {
                ${tw`text-neutral-100`};
                box-shadow: inset 0 -2px ${theme`colors.green.600`.toString()};
            }
        }
    }
`;

export default SubNavigation;
