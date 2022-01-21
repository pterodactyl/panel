import React, { memo, useCallback } from 'react';
import { useField } from 'formik';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import isEqual from 'react-fast-compare';

interface Props {
	isEditable: boolean;
	permissions: string[];
	className?: string;
}

const PermissionTitleBox: React.FC<Props> = memo(({ isEditable, permissions, className }) => {
	const [ { value }, , { setValue } ] = useField<string[]>('permissions');

	const onCheckboxClicked = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
		if (e.currentTarget.checked) {
			setValue([
				...value,
				...permissions.filter(p => !value.includes(p)),
			]);
		} else {
			setValue(value.filter(p => !permissions.includes(p)));
		}
	}, [ permissions, value ]);

	return (
		<>
			{isEditable && <Input css={tw`mr-1`} type={'checkbox'} checked={permissions.every((p) => value.includes(p))} onChange={onCheckboxClicked} />}
		</>
	);
}, isEqual);

export default PermissionTitleBox;
