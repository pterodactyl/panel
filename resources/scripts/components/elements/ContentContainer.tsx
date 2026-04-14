import styled from 'styled-components/macro';
import { breakpoint } from '@/theme';

const ContentContainer = styled.div`
    max-width: 1200px;
    margin-left: 1rem;
    margin-right: 1rem;

    ${breakpoint('xl')`
        margin-left: auto;
        margin-right: auto;
    `};
`;
ContentContainer.displayName = 'ContentContainer';

export default ContentContainer;
