import styled from 'styled-components/macro';
import Checkbox from '@/components/elements/Checkbox';
import React from 'react';
import { useStoreState } from 'easy-peasy';
import Label from '@/components/elements/Label';

const Container = styled.label`
    display: flex;
    align-items: center;
    border-width: 1px;
    border-color: transparent;
    border-radius: 0.25rem;
    padding: 0.5rem;
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 75ms;
    text-transform: none;

    &:not(.disabled) {
        cursor: pointer;

        &:hover {
            border-color: hsl(211, 12%, 43%);
            background-color: hsl(209, 20%, 25%);
        }
    }

    &:not(:first-of-type) {
        margin-top: 1rem;
        @media (min-width: 640px) {
            margin-top: 0.5rem;
        }
    }

    &.disabled {
        opacity: 0.5;

        & input[type='checkbox']:not(:checked) {
            border-width: 0px;
        }
    }
`;

interface Props {
    permission: string;
    disabled: boolean;
}

const PermissionRow = ({ permission, disabled }: Props) => {
    const [key, pkey] = permission.split('.', 2);
    const permissions = useStoreState((state) => state.permissions.data);

    return (
        <Container htmlFor={`permission_${permission}`} className={disabled ? 'disabled' : undefined}>
            <div className={'p-2'}>
                <Checkbox
                    id={`permission_${permission}`}
                    name={'permissions'}
                    value={permission}
                    className={'w-5 h-5 mr-2'}
                    disabled={disabled}
                />
            </div>
            <div className={'flex-1'}>
                <Label as={'p'} className={'font-medium'}>
                    {pkey}
                </Label>
                {permissions[key].keys[pkey].length > 0 && (
                    <p className={'text-xs text-neutral-400 mt-1'}>{permissions[key].keys[pkey]}</p>
                )}
            </div>
        </Container>
    );
};

export default PermissionRow;
