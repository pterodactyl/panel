import React from 'react';
import tw, { styled } from 'twin.macro';
import Input from '@/components/elements/Input';
import { ServerContext } from '@/state/server';

export const FileActionCheckbox = styled(Input)`
    && {
        ${tw`border-neutral-500 bg-transparent`};

        &:not(:checked) {
            ${tw`hover:border-neutral-300`};
        }
    }
`;

export default ({ name }: { name: string }) => {
    const isChecked = ServerContext.useStoreState(state => state.files.selectedFiles.indexOf(name) >= 0);
    const appendSelectedFile = ServerContext.useStoreActions(actions => actions.files.appendSelectedFile);
    const removeSelectedFile = ServerContext.useStoreActions(actions => actions.files.removeSelectedFile);

    return (
        <label css={tw`flex-none p-4 absolute self-center z-30 cursor-pointer`}>
            <FileActionCheckbox
                name={'selectedFiles'}
                value={name}
                checked={isChecked}
                type={'checkbox'}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    if (e.currentTarget.checked) {
                        appendSelectedFile(name);
                    } else {
                        removeSelectedFile(name);
                    }
                }}
            />
        </label>
    );
};
