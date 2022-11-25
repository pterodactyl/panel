import { useField } from 'formik';
import type { ReactNode } from 'react';
import { memo, useCallback } from 'react';
import isEqual from 'react-fast-compare';
import tw from 'twin.macro';

import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Input from '@/components/elements/Input';

interface Props {
    children?: ReactNode;
    className?: string;

    isEditable?: boolean;
    title: string;
    permissions: string[];
}

function PermissionTitleBox({ isEditable, title, permissions, className, children }: Props) {
    const [{ value }, , { setValue }] = useField<string[]>('permissions');

    const onCheckboxClicked = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            if (e.currentTarget.checked) {
                setValue([...value, ...permissions.filter(p => !value.includes(p))]);
            } else {
                setValue(value.filter(p => !permissions.includes(p)));
            }
        },
        [permissions, value],
    );

    return (
        <TitledGreyBox
            title={
                <div css={tw`flex items-center`}>
                    <p css={tw`text-sm uppercase flex-1`}>{title}</p>
                    {isEditable && (
                        <Input
                            type={'checkbox'}
                            checked={permissions.every(p => value.includes(p))}
                            onChange={onCheckboxClicked}
                        />
                    )}
                </div>
            }
            className={className}
        >
            {children}
        </TitledGreyBox>
    );
}

const MemoizedPermissionTitleBox = memo(PermissionTitleBox, isEqual);

export default MemoizedPermissionTitleBox;
