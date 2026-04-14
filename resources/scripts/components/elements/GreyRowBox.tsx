import styled, { css } from 'styled-components';

export default styled.div<{ $hoverable?: boolean }>`
    display: flex;
    border-radius: 0.25rem;
    text-decoration-line: none;
    color: hsl(210, 16%, 82%);
    align-items: center;
    background-color: hsl(209, 18%, 30%);
    padding: 1rem;
    border-width: 1px;
    border-color: transparent;
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
    overflow: hidden;

    ${(props) =>
        props.$hoverable !== false &&
        css`
            &:hover {
                border-color: hsl(211, 12%, 43%);
            }
        `};

    & .icon {
        border-radius: 9999px;
        width: 4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: hsl(211, 12%, 43%);
        padding: 0.75rem;
    }
`;
