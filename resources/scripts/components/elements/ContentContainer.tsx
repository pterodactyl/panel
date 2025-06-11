import styled from 'styled-components/macro';
import { breakpoint } from '@/theme';
import tw from 'twin.macro';

const ContentContainer = styled.div`
    ${tw`mt-4`}
    ${tw`mx-4`};

    ${breakpoint('xl')`
        ${tw`mx-auto`};
    `};
`;
ContentContainer.displayName = 'ContentContainer';
ContentContainer.defaultProps = {
    className: 'content-container',
};

export default ContentContainer;
