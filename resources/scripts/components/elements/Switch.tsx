import React, { useMemo } from 'react';
import styled from 'styled-components/macro';
import v4 from 'uuid/v4';
import tw from 'twin.macro';

const ToggleContainer = styled.div`
    ${tw`relative select-none w-12 leading-normal`};

    & > input[type="checkbox"] {
        ${tw`hidden`};

        &:checked + label {
            ${tw`bg-primary-500 border-primary-700 shadow-none`};
        }

        &:checked + label:before {
            right: 0.125rem;
        }
    }

    & > label {
        ${tw`mb-0 block overflow-hidden cursor-pointer bg-neutral-400 border border-neutral-700 rounded-full h-6 shadow-inner`};
        transition: all 75ms linear;

        &::before {
            ${tw`absolute block bg-white border h-5 w-5 rounded-full`};
            top: 0.125rem;
            right: calc(50% + 0.125rem);
            //width: 1.25rem;
            //height: 1.25rem;
            content: "";
            transition: all 75ms ease-in;
        }
    }
`;

export interface SwitchProps {
    name: string;
    label?: string;
    description?: string;
    defaultChecked?: boolean;
    onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
    children?: React.ReactNode;
}

const Switch = ({ name, label, description, defaultChecked, onChange, children }: SwitchProps) => {
    const uuid = useMemo(() => v4(), []);

    return (
        <div css={tw`flex items-center`}>
            <ToggleContainer css={tw`flex-none`}>
                {children
                || <input
                    id={uuid}
                    name={name}
                    type={'checkbox'}
                    onChange={e => onChange && onChange(e)}
                    defaultChecked={defaultChecked}
                />
                }
                <label htmlFor={uuid}/>
            </ToggleContainer>
            {(label || description) &&
            <div css={tw`ml-4 w-full`}>
                {label &&
                <label
                    css={[ tw`cursor-pointer`, !!description && tw`mb-0` ]}
                    className={'input-dark-label'}
                    htmlFor={uuid}
                >
                    {label}
                </label>
                }
                {description &&
                <p className={'input-help'}>
                    {description}
                </p>
                }
            </div>
            }
        </div>
    );
};

export default Switch;
