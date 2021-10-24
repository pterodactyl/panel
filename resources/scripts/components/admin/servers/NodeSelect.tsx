import React, { useState } from 'react';
import { useFormikContext } from 'formik';
import SearchableSelect, { Option } from '@/components/elements/SearchableSelect';
import { Node, searchNodes } from '@/api/admin/node';

export default ({ selected }: { selected?: Node }) => {
    const context = useFormikContext();

    const [ node, setNode ] = useState<Node | null>(selected || null);
    const [ nodes, setNodes ] = useState<Node[] | null>(null);

    const onSearch = async (query: string) => {
        setNodes(
            await searchNodes({ filters: { name: query } }),
        );
    };

    const onSelect = (node: Node | null) => {
        setNode(node);
        context.setFieldValue('ownerId', node?.id || null);
    };

    const getSelectedText = (node: Node | null): string => node?.name || '';

    return (
        <SearchableSelect
            id={'nodeId'}
            name={'nodeId'}
            label={'Node'}
            placeholder={'Select a node...'}
            items={nodes}
            selected={node}
            setSelected={setNode}
            setItems={setNodes}
            onSearch={onSearch}
            onSelect={onSelect}
            getSelectedText={getSelectedText}
            nullable
        >
            {nodes?.map(d => (
                <Option key={d.id} selectId={'nodeId'} id={d.id} item={d} active={d.id === node?.id}>
                    {d.name}
                </Option>
            ))}
        </SearchableSelect>
    );
};
