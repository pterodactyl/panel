import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import React, { useEffect, useState } from 'react';
import { Egg } from '@/api/admin/eggs/getEgg';
import searchEggs from '@/api/admin/nests/searchEggs';

export default ({ nestId, egg, setEgg }: { nestId: number | null; egg: Egg | null, setEgg: (value: Egg | null) => void }) => {
    const [ eggs, setEggs ] = useState<Egg[]>([]);

    useEffect(() => {
        if (nestId === null) {
            return;
        }

        searchEggs(nestId, {}, [ 'variables' ])
            .then(eggs => {
                setEggs(eggs);
                if (eggs.length < 1) {
                    setEgg(null);
                    return;
                }
                setEgg(eggs[0]);
            })
            .catch(error => console.error(error));
    }, [ nestId ]);

    return (
        <>
            <Label>Egg</Label>
            <Select
                defaultValue={egg?.id || undefined}
                id={'eggId'}
                name={'eggId'}
                onChange={e => setEgg(eggs.find(egg => egg.id.toString() === e.currentTarget.value) || null)}
            >
                {eggs.map(v => (
                    <option key={v.id} value={v.id.toString()}>
                        {v.name}
                    </option>
                ))}
            </Select>
        </>
    );
};
