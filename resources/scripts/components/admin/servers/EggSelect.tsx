import { useField } from 'formik';
import type { ChangeEvent } from 'react';
import { useEffect, useState } from 'react';

import type { WithRelationships } from '@/api/admin';
import type { Egg } from '@/api/admin/egg';
import { searchEggs } from '@/api/admin/egg';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';

interface Props {
    nestId?: number;
    selectedEggId?: number;
    onEggSelect: (egg: Egg | null) => void;
}

export default ({ nestId, selectedEggId, onEggSelect }: Props) => {
    const [, , { setValue, setTouched }] = useField<Record<string, string | undefined>>('environment');
    const [eggs, setEggs] = useState<WithRelationships<Egg, 'variables'>[] | null>(null);

    const selectEgg = (egg: Egg | null) => {
        if (egg === null) {
            onEggSelect(null);
            return;
        }

        // Clear values
        setValue({});
        setTouched(true);

        onEggSelect(egg);

        const values: Record<string, any> = {};
        egg.relationships.variables?.forEach(v => {
            values[v.environmentVariable] = v.defaultValue;
        });
        setValue(values);
        setTouched(true);
    };

    useEffect(() => {
        if (!nestId) {
            setEggs(null);
            return;
        }

        searchEggs(nestId, {})
            .then(eggs => {
                setEggs(eggs);
                selectEgg(eggs[0] || null);
            })
            .catch(error => console.error(error));
    }, [nestId]);

    const onSelectChange = (e: ChangeEvent<HTMLSelectElement>) => {
        selectEgg(eggs?.find(egg => egg.id.toString() === e.currentTarget.value) || null);
    };

    return (
        <>
            <Label>Egg</Label>
            <Select id={'eggId'} name={'eggId'} defaultValue={selectedEggId} onChange={onSelectChange}>
                {!eggs ? (
                    <option disabled>Loading...</option>
                ) : (
                    eggs.map(v => (
                        <option key={v.id} value={v.id.toString()}>
                            {v.name}
                        </option>
                    ))
                )}
            </Select>
        </>
    );
};
