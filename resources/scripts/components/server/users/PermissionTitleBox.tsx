import React, { memo, useCallback } from 'react';
import { useField } from 'formik';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import isEqual from 'react-fast-compare';

interface Props {
    isEditable: boolean;
    title: string;
    permissions: string[];
    editablePermissions: string[];
    className?: string;
}

const PermissionTitleBox: React.FC<Props> = memo(({ isEditable, title, permissions, editablePermissions, className, children }) => {
    const [{ value }, , { setValue }] = useField<string[]>('permissions');

    const onCheckboxClicked = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            if (e.currentTarget.checked) {
                setValue([...value, ...permissions.filter((p) => !value.includes(p) && editablePermissions.includes(p))]);
            } else {
                setValue(value.filter((p) => !editablePermissions.includes(p)));
            }
        },
        [permissions, value]
    );

    return (
        <TitledGreyBox
            title={
                <div css={tw`flex items-center`}>
                    <p css={tw`text-sm uppercase flex-1`}>{title}</p>
                    {isEditable && (
                        <Input
                            type={'checkbox'}
                            checked={editablePermissions.every((p) => value.includes(p)) && value.find((p) => p.startsWith(title)) != null}
                            onChange={onCheckboxClicked}
                            disabled={editablePermissions.filter((p) => p.startsWith(title)).length === 0}
                        />
                    )}
                </div>
            }
            className={className}
        >
            {children}
        </TitledGreyBox>
    );
}, isEqual);

export default PermissionTitleBox;
