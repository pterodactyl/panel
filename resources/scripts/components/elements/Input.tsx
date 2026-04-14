import styled, { css } from 'styled-components';

export interface Props {
    isLight?: boolean;
    hasError?: boolean;
}

const light = css<Props>`
    background-color: #ffffff;
    border-color: hsl(210, 16%, 82%);
    color: hsl(209, 20%, 25%);

    &:focus {
        border-color: #60a5fa;
    }

    &:disabled {
        background-color: hsl(214, 15%, 91%);
        border-color: hsl(210, 16%, 82%);
    }
`;

const checkboxStyle = css<Props>`
    background-color: hsl(211, 12%, 43%);
    cursor: pointer;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    display: inline-block;
    vertical-align: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    flex-shrink: 0;
    width: 1rem;
    height: 1rem;
    color: #60a5fa;
    border-width: 1px;
    border-color: hsl(211, 13%, 65%);
    border-radius: 0.125rem;
    print-color-adjust: exact;
    background-origin: border-box;
    transition: all 75ms linear, box-shadow 25ms linear;

    &:checked {
        border-color: transparent;
        background-repeat: no-repeat;
        background-position: center;
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M5.707 7.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4a1 1 0 0 0-1.414-1.414L7 8.586 5.707 7.293z'/%3e%3c/svg%3e");
        background-color: currentColor;
        background-size: 100% 100%;
    }

    &:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        border-color: #93c5fd;
        box-shadow: 0 0 0 1px rgba(9, 103, 210, 0.25);
    }
`;

const inputStyle = css<Props>`
    // Reset to normal styling.
    resize: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    outline: 2px solid transparent;
    outline-offset: 2px;
    width: 100%;
    min-width: 0px;
    padding: 0.75rem;
    border-width: 2px;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
    background-color: hsl(209, 14%, 37%);
    border-color: hsl(211, 12%, 43%);
    color: hsl(210, 16%, 82%);
    box-shadow: 0 0 #0000;

    &:hover {
        border-color: hsl(211, 10%, 53%);
    }

    & + .input-help {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        line-height: 1rem;
        ${(props) => (props.hasError ? `color: #fecaca;` : `color: hsl(210, 16%, 82%);`)};
    }

    &:required,
    &:invalid {
        box-shadow: 0 0 #0000;
    }

    &:not(:disabled):not(:read-only):focus {
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1), 0 0 0 2px rgba(96, 165, 250, 0.5);
        border-color: #93c5fd;
        ${(props) =>
            props.hasError &&
            `border-color: #fca5a5; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1), 0 0 0 2px rgba(254, 202, 202, 0.5);`};
    }

    &:disabled {
        opacity: 0.75;
    }

    ${(props) => props.isLight && light};
    ${(props) =>
        props.hasError &&
        css`
            color: #fee2e2;
            border-color: #f87171;
            &:hover {
                border-color: #fca5a5;
            }
        `};
`;

const Input = styled.input<Props>`
    &:not([type='checkbox']):not([type='radio']) {
        ${inputStyle};
    }

    &[type='checkbox'],
    &[type='radio'] {
        ${checkboxStyle};

        &[type='radio'] {
            border-radius: 9999px;
        }
    }
`;
const Textarea = styled.textarea<Props>`
    ${inputStyle}
`;

export { Textarea };
export default Input;
