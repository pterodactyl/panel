import styled, { css } from 'styled-components/macro';

interface Props {
    hideDropdownArrow?: boolean;
}

const Select = styled.select<Props>`
    box-shadow: 0 0 #0000;
    display: block;
    padding: 0.75rem;
    padding-right: 2rem;
    border-radius: 0.25rem;
    border-width: 1px;
    width: 100%;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition-property: color, background-color, border-color;
    transition-timing-function: linear;
    transition-duration: 150ms;

    &,
    &:hover:not(:disabled),
    &:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
    }

    -webkit-appearance: none;
    -moz-appearance: none;
    background-size: 1rem;
    background-repeat: no-repeat;
    background-position-x: calc(100% - 0.75rem);
    background-position-y: center;

    &::-ms-expand {
        display: none;
    }

    ${(props) =>
        !props.hideDropdownArrow &&
        css`
            background-color: hsl(209, 14%, 37%);
            border-color: hsl(211, 12%, 43%);
            color: hsl(210, 16%, 82%);
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='%23C3D1DF' d='M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z'/%3e%3c/svg%3e ");

            &:hover:not(:disabled),
            &:focus {
                border-color: hsl(211, 10%, 53%);
            }
        `};
`;

export default Select;
