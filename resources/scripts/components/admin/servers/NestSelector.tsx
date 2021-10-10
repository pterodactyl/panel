import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import React, { useEffect, useState } from 'react';
import { Nest, searchNests } from '@/api/admin/nest';

interface Props {
    selectedNestId?: number;
    onNestSelect: (nest: number) => void;
}

export default ({ selectedNestId, onNestSelect }: Props) => {
    const [ nests, setNests ] = useState<Nest[] | null>(null);

    useEffect(() => {
        searchNests({})
            .then(setNests)
            .catch(error => console.error(error));
    }, []);

    return (
        <>
            <Label>Nest</Label>
            <Select value={selectedNestId} onChange={e => onNestSelect(Number(e.currentTarget.value))}>
                {!nests ?
                    <option disabled>Loading...</option>
                    :
                    nests?.map(v => (
                        <option key={v.uuid} value={v.id.toString()}>
                            {v.name}
                        </option>
                    ))
                }
            </Select>
        </>
    );
};
