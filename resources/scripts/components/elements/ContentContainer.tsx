import { breakpoint } from '@/theme';
import tw, { styled } from 'twin.macro';

const ContentContainer = styled.div`
    max-width: 1200px;
    ${tw`mx-4`};

    ${breakpoint('xl')`
        ${tw`mx-auto`};
    `};
`;
ContentContainer.displayName = 'ContentContainer';

export default ContentContainer;
