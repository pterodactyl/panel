import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import { useField } from 'formik';
import React, { useEffect, useState } from 'react';
import { Egg, searchEggs } from '@/api/admin/egg';
import { WithRelationships } from '@/api/admin';

interface Props {
    nestId?: number;
    selectedEggId?: number;
    onEggSelect: (egg: Egg | null) => void;
}

export default ({ nestId, selectedEggId, onEggSelect }: Props) => {
    const [ , , { setValue, setTouched } ] = useField<Record<string, string | undefined>>('environment');
    const [ eggs, setEggs ] = useState<WithRelationships<Egg, 'variables'>[] | null>(null);

    useEffect(() => {
        if (!nestId) return setEggs(null);

        searchEggs(nestId, {}).then(eggs => {
            setEggs(eggs);
            onEggSelect(eggs[0] || null);
        }).catch(error => console.error(error));
    }, [ nestId ]);

    const onSelectChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        if (!eggs) return;

        const match = eggs.find(egg => String(egg.id) === e.currentTarget.value);
        if (!match) return onEggSelect(null);

        // Ensure that only new egg variables are present in the record storing all
        // of the possible variables. This ensures the fields are controlled, rather
        // than uncontrolled when a user begins typing in them.
        setValue(match.relationships.variables.reduce((obj, value) => ({
            ...obj,
            [value.environmentVariable]: undefined,
        }), {}));
        setTouched(true);

        onEggSelect(match);
    };

    return (
        <>
            <Label>Egg</Label>
            <Select id={'eggId'} name={'eggId'} defaultValue={selectedEggId} onChange={onSelectChange}>
                {!eggs ?
                    <option disabled>Loading...</option>
                    :
                    eggs.map(v => (
                        <option key={v.id} value={v.id.toString()}>
                            {v.name}
                        </option>
                    ))
                }
            </Select>
        </>
    );
};
