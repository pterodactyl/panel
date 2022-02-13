import tw, { styled } from 'twin.macro';
import React from 'react';
import { useStoreState } from 'easy-peasy';
import Label from '@/components/elements/Label';
import { Field, FieldProps } from 'formik';
import Input from '@/components/elements/Input';

interface CheckboxProps {
    name: string;
    value: string;
    className?: string;
}

type CheckboxOmitFields = 'ref' | 'name' | 'value' | 'type' | 'checked' | 'onClick' | 'onChange';

type InputProps = Omit<JSX.IntrinsicElements['input'], CheckboxOmitFields> & CheckboxProps;

const Checkbox = ({ name, value, className, ...props }: InputProps) => (
    <Field name={name}>
        {({ field, form }: FieldProps) => {
            if (!Array.isArray(field.value)) {
                console.error('Attempting to mount a checkbox using a field value that is not an array.');
                return null;
            }

            return (
                <Input
                    {...field}
                    {...props}
                    className={className}
                    type={'checkbox'}
                    checked={(field.value || []).includes(value)}
                    onClick={() => form.setFieldTouched(field.name, true)}
                    onChange={e => {
                        const set = new Set(field.value);
                        set.has(value) ? set.delete(value) : set.add(value);

                        field.onChange(e);
                        form.setFieldValue(field.name, Array.from(set));
                    }}
                />
            );
        }}
    </Field>
);

const Container = styled.label`
  ${tw`flex items-center border border-transparent rounded md:p-2 transition-colors duration-75`};
  text-transform: none;

  &:not(.disabled) {
      ${tw`cursor-pointer`};

      &:hover {
        ${tw`border-neutral-500 bg-neutral-800`};
      }
  }

  &:not(:first-of-type) {
      ${tw`mt-4 sm:mt-2`};
  }

  &.disabled {
      ${tw`opacity-50`};

      & input[type="checkbox"]:not(:checked) {
          ${tw`border-0`};
      }
  }
`;

interface Props {
    permission: string;
    disabled: boolean;
}

const PermissionRow = ({ permission, disabled }: Props) => {
    const [ key, pkey ] = permission.split('.', 2);
    const permissions = useStoreState(state => state.permissions.data);

    return (
        <Container htmlFor={`permission_${permission}`} className={disabled ? 'disabled' : undefined}>
            <div css={tw`p-2`}>
                <Checkbox
                    id={`permission_${permission}`}
                    name={'permissions'}
                    value={permission}
                    css={tw`w-5 h-5 mr-2`}
                    disabled={disabled}
                />
            </div>
            <div css={tw`flex-1`}>
                <Label as={'p'} css={tw`font-medium`}>{pkey}</Label>
                {permissions[key].keys[pkey].length > 0 &&
                <p css={tw`text-xs text-neutral-400 mt-1`}>
                    {permissions[key].keys[pkey]}
                </p>
                }
            </div>
        </Container>
    );
};

export default PermissionRow;
