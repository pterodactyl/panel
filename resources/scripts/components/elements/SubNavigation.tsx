import styled from 'styled-components';

const SubNavigation = styled.div`
    width: 100%;
    background-color: hsl(209, 18%, 30%);
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    overflow-x: auto;

    & > div {
        display: flex;
        align-items: center;
        font-size: 0.875rem;
        line-height: 1.25rem;
        margin-left: auto;
        margin-right: auto;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        max-width: 1200px;

        & > a,
        & > div {
            display: inline-block;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            padding-left: 1rem;
            padding-right: 1rem;
            color: hsl(211, 13%, 65%);
            text-decoration-line: none;
            white-space: nowrap;
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;

            &:not(:first-of-type) {
                margin-left: 0.5rem;
            }

            &:hover {
                color: hsl(214, 15%, 91%);
            }

            &:active,
            &.active {
                color: hsl(214, 15%, 91%);
                box-shadow: inset 0 -2px #0891b2;
            }
        }
    }
`;

export default SubNavigation;
