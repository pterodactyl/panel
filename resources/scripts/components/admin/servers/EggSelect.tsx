import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import { useFormikContext } from 'formik';
import React, { useEffect, useState } from 'react';
import { Egg } from '@/api/admin/eggs/getEgg';
import searchEggs from '@/api/admin/nests/searchEggs';

export default ({ nestId, egg, setEgg }: { nestId: number | null; egg: Egg | null, setEgg: (value: Egg | null) => void }) => {
    const { setFieldValue } = useFormikContext();

    const [ eggs, setEggs ] = useState<Egg[]>([]);

    /**
     * So you may be asking yourself, "what cluster-fuck of code is this?"
     *
     * Well, this code makes sure that when the egg changes, that the environment
     * object has empty string values instead of undefined so React doesn't think
     * the variable fields are uncontrolled.
     */
    const setEgg2 = (newEgg: Egg | null) => {
        if (newEgg === null) {
            setEgg(null);
            return;
        }

        // Reset all variables to be empty, don't inherit the previous values.
        const newVariables = newEgg?.relations.variables;
        newVariables?.forEach(v => setFieldValue('environment.' + v.envVariable, ''));
        const variables = egg?.relations.variables?.filter(v => newVariables?.find(v2 => v2.envVariable === v.envVariable) === undefined);

        setEgg(newEgg);

        // Clear any variables that don't exist on the new egg.
        variables?.forEach(v => setFieldValue('environment.' + v.envVariable, undefined));
    };

    useEffect(() => {
        if (nestId === null) {
            return;
        }

        searchEggs(nestId, {}, [ 'variables' ])
            .then(eggs => {
                setEggs(eggs);
                if (eggs.length < 1) {
                    setEgg2(null);
                    return;
                }
                setEgg2(eggs[0]);
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
                onChange={e => setEgg2(eggs.find(egg => egg.id.toString() === e.currentTarget.value) || null)}
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
