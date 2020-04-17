import styled from 'styled-components';
import { breakpoint } from 'styled-components-breakpoint';

const ContentContainer = styled.div`
    max-width: 1200px;
    ${tw`mx-4`};

    ${breakpoint('xl')`
        ${tw`mx-auto`};
    `};
`;

export default ContentContainer;
