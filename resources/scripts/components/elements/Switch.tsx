import React, { useMemo } from 'react';
import styled from 'styled-components';
import v4 from 'uuid/v4';
import classNames from 'classnames';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import { Field, FieldProps } from 'formik';

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

interface Props {
    name: string;
    description?: string;
    label: string;
    enabled?: boolean;
}

const Switch = ({ name, label, description }: Props) => {
    const uuid = useMemo(() => v4(), []);

    return (
        <FormikFieldWrapper name={name}>
            <div className={'flex items-center'}>
                <ToggleContainer className={'mr-4 flex-none'}>
                    <Field name={name}>
                        {({ field, form }: FieldProps) => (
                            <input
                                id={uuid}
                                name={name}
                                type={'checkbox'}
                                onChange={() => {
                                    form.setFieldTouched(name);
                                    form.setFieldValue(field.name, !field.value);
                                }}
                                defaultChecked={field.value}
                            />
                        )}
                    </Field>
                    <label htmlFor={uuid}/>
                </ToggleContainer>
                <div className={'w-full'}>
                    <label
                        className={classNames('input-dark-label cursor-pointer', { 'mb-0': !!description })}
                        htmlFor={uuid}
                    >{label}</label>
                    {description &&
                    <p className={'input-help'}>
                        {description}
                    </p>
                    }
                </div>
            </div>
        </FormikFieldWrapper>
    );
};

export default Switch;
