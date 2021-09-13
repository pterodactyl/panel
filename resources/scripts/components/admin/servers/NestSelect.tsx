import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import React, { useEffect, useState } from 'react';
import { Nest } from '@/api/admin/nests/getNests';
import searchNests from '@/api/admin/nests/searchNests';

export default ({ nestId, setNestId }: { nestId: number | null; setNestId: (value: number | null) => void }) => {
    const [ nests, setNests ] = useState<Nest[] | null>(null);

    useEffect(() => {
        console.log(nestId || undefined);

        searchNests({})
            .then(nests => setNests(nests))
            .catch(error => console.error(error));
    }, []);

    return (
        <>
            <Label>Nest</Label>
            <Select value={nestId || undefined} onChange={e => setNestId(Number(e.currentTarget.value))}>
                {nests?.map(v => (
                    <option key={v.id} value={v.id.toString()}>
                        {v.name}
                    </option>
                ))}
            </Select>
        </>
    );
};
