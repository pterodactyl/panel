import { useEffect, useState } from 'react';

import type { Nest } from '@/api/admin/nest';
import { searchNests } from '@/api/admin/nest';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';

interface Props {
    selectedNestId?: number;
    onNestSelect: (nest: number) => void;
}

export default ({ selectedNestId, onNestSelect }: Props) => {
    const [nests, setNests] = useState<Nest[] | null>(null);

    useEffect(() => {
        searchNests({})
            .then(nests => {
                setNests(nests);
                if (selectedNestId === 0 && nests.length > 0) {
                    // @ts-expect-error go away
                    onNestSelect(nests[0].id);
                }
            })
            .catch(error => console.error(error));
    }, []);

    return (
        <>
            <Label>Nest</Label>
            <Select value={selectedNestId} onChange={e => onNestSelect(Number(e.currentTarget.value))}>
                {!nests ? (
                    <option disabled>Loading...</option>
                ) : (
                    nests?.map(v => (
                        <option key={v.uuid} value={v.id.toString()}>
                            {v.name}
                        </option>
                    ))
                )}
            </Select>
        </>
    );
};
