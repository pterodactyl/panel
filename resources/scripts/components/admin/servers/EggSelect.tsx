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
    onEggSelect: (egg: WithRelationships<Egg, 'variables'> | undefined) => void;
}

export default ({ nestId, selectedEggId, onEggSelect }: Props) => {
    const [, , { setValue, setTouched }] = useField<Record<string, string | undefined>>('environment');
    const [eggs, setEggs] = useState<WithRelationships<Egg, 'variables'>[] | undefined>(undefined);

    const selectEgg = (egg: WithRelationships<Egg, 'variables'> | undefined) => {
        if (egg === undefined) {
            onEggSelect(undefined);
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
            setEggs(undefined);
            return;
        }

        searchEggs(nestId, {})
            .then(_eggs => {
                setEggs(_eggs);

                // If the currently selected egg is in the selected nest, use it instead of picking the first egg on the nest.
                const egg = _eggs.find(egg => egg.id === selectedEggId) ?? _eggs[0];
                selectEgg(egg);
            })
            .catch(error => console.error(error));
    }, [nestId]);

    const onSelectChange = (event: ChangeEvent<HTMLSelectElement>) => {
        selectEgg(eggs?.find(egg => egg.id.toString() === event.currentTarget.value) ?? undefined);
    };

    return (
        <>
            <Label>Egg</Label>
            <Select id={'eggId'} name={'eggId'} value={selectedEggId} onChange={onSelectChange}>
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
