import React, { useMemo } from 'react';
import styled from 'styled-components/macro';
import { v4 } from 'uuid';
import Label from '@/components/elements/Label';
import Input from '@/components/elements/Input';
import classNames from 'classnames';

const ToggleContainer = styled.div`
    position: relative;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    width: 3rem;
    line-height: 1.5;

    & > input[type='checkbox'] {
        display: none;

        &:checked + label {
            background-color: #3b82f6;
            border-color: #1d4ed8;
            box-shadow: 0 0 #0000;
        }

        &:checked + label:before {
            right: 0.125rem;
        }
    }

    & > label {
        margin-bottom: 0px;
        display: block;
        overflow: hidden;
        cursor: pointer;
        background-color: hsl(211, 10%, 53%);
        border-width: 1px;
        border-color: hsl(209, 18%, 30%);
        border-radius: 9999px;
        height: 1.5rem;
        box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.05);
        transition: all 75ms linear;

        &::before {
            position: absolute;
            display: block;
            background-color: #ffffff;
            border-width: 1px;
            height: 1.25rem;
            width: 1.25rem;
            border-radius: 9999px;
            top: 0.125rem;
            right: calc(50% + 0.125rem);
            content: '';
            transition: all 75ms ease-in;
        }
    }
`;

export interface SwitchProps {
    name: string;
    label?: string;
    description?: string;
    defaultChecked?: boolean;
    readOnly?: boolean;
    onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
    children?: React.ReactNode;
}

const Switch = ({ name, label, description, defaultChecked, readOnly, onChange, children }: SwitchProps) => {
    const uuid = useMemo(() => v4(), []);

    return (
        <div className={'flex items-center'}>
            <ToggleContainer className={'flex-none'}>
                {children || (
                    <Input
                        id={uuid}
                        name={name}
                        type={'checkbox'}
                        onChange={(e) => onChange && onChange(e)}
                        defaultChecked={defaultChecked}
                        disabled={readOnly}
                    />
                )}
                <Label htmlFor={uuid} />
            </ToggleContainer>
            {(label || description) && (
                <div className={'ml-4 w-full'}>
                    {label && (
                        <Label className={classNames('cursor-pointer', !!description && 'mb-0')} htmlFor={uuid}>
                            {label}
                        </Label>
                    )}
                    {description && <p className={'text-neutral-400 text-sm mt-2'}>{description}</p>}
                </div>
            )}
        </div>
    );
};

export default Switch;
