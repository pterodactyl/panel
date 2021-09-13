import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import React, { useEffect, useState } from 'react';
import { Egg } from '@/api/admin/eggs/getEgg';
import searchEggs from '@/api/admin/nests/searchEggs';

export default ({ nestId, eggId }: { nestId: number | null; eggId?: number }) => {
    const [ eggs, setEggs ] = useState<Egg[]>([]);

    useEffect(() => {
        if (nestId === null) {
            return;
        }

        searchEggs(nestId, {})
            .then(eggs => setEggs(eggs))
            .catch(error => console.error(error));
    }, [ nestId ]);

    return (
        <>
            <Label>Egg</Label>
            <Select defaultValue={eggId || undefined} id={'eggId'} name={'eggId'}>
                {eggs.map(v => (
                    <option key={v.id} value={v.id.toString()}>
                        {v.name}
                    </option>
                ))}
            </Select>
        </>
    );
};
