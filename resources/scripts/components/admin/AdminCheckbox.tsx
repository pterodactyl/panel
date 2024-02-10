import type { ChangeEvent } from 'react';
import tw, { styled } from 'twin.macro';

import Input from '@/components/elements/Input';

export const TableCheckbox = styled(Input)`
    && {
        ${tw`border-neutral-500 bg-transparent`};

        &:not(:checked) {
            ${tw`hover:border-neutral-300`};
        }
    }
`;

export default ({
    name,
    checked,
    onChange,
}: {
    name: string;
    checked: boolean;
    onChange(e: ChangeEvent<HTMLInputElement>): void;
}) => {
    return (
        <div css={tw`flex items-center`}>
            <TableCheckbox
                type={'checkbox'}
                name={'selectedItems'}
                value={name}
                checked={checked}
                onChange={onChange}
            />
        </div>
    );
};
