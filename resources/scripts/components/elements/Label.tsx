import styled from 'styled-components/macro';

const Label = styled.label<{ isLight?: boolean }>`
    display: block;
    font-size: 0.75rem;
    line-height: 1rem;
    text-transform: uppercase;
    color: hsl(210, 16%, 82%);
    margin-bottom: 0.25rem;
    @media (min-width: 640px) {
        margin-bottom: 0.5rem;
    }
    ${(props) => props.isLight && `color: hsl(209, 18%, 30%);`};
`;

export default Label;
