import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';

const ContentContainer = styled.div`
    ${tw`mx-4`};

    ${breakpoint('xl')`
        ${tw`ml-36 mr-16`};
    `};
`;

ContentContainer.displayName = 'ContentContainer';

export default ContentContainer;
