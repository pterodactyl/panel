import Input from '@/components/elements/Input';
import React from 'react';
import styled from 'styled-components/macro';
import tw from 'twin.macro';

const Checkbox = styled(Input)`
    && {
        ${tw`border-neutral-500 bg-transparent`};

        &:not(:checked) {
            ${tw`hover:border-neutral-300`};
        }
    }
`;

export default ({ name }: { name: string }) => {
    return (
        <Checkbox
            name={'selectedItems'}
            value={name}
            type={'checkbox'}
        />
    );
};
