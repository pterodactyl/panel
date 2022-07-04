import tw from 'twin.macro';
import { useField } from 'formik';
import isEqual from 'react-fast-compare';
import Input from '@/components/elements/Input';
import React, { memo, useCallback } from 'react';

interface Props {
    isEditable: boolean;
    permissions: string[];
}

const SelectAllPermissions: React.FC<Props> = memo(({ isEditable, permissions }) => {
    const [{ value }, , { setValue }] = useField<string[]>('permissions');

    const onCheckboxClicked = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            if (e.currentTarget.checked) {
                setValue([...value, ...permissions.filter((p) => !value.includes(p))]);
            } else {
                setValue(value.filter((p) => !permissions.includes(p)));
            }
        },
        [permissions, value]
    );

    return (
        <>
            {isEditable && (
                <Input
                    css={tw`mr-1`}
                    type={'checkbox'}
                    checked={permissions.every((p) => value.includes(p))}
                    onChange={onCheckboxClicked}
                />
            )}
        </>
    );
}, isEqual);

export default SelectAllPermissions;
