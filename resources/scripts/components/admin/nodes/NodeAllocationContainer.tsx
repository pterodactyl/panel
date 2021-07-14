import Label from '@/components/elements/Label';
import React, { useEffect, useState } from 'react';
import AdminBox from '@/components/admin/AdminBox';
import Creatable from 'react-select/creatable';
import { ActionMeta, GroupTypeBase, InputActionMeta, ValueType } from 'react-select/src/types';
import { SelectStyle } from '@/components/elements/Select2';
import tw from 'twin.macro';
import getAllocations from '@/api/admin/nodes/getAllocations';
import { useRouteMatch } from 'react-router-dom';

interface Option {
    value: string;
    label: string;
}

const distinct = (value: any, index: any, self: any) => {
    return self.indexOf(value) === index;
};

export default () => {
    const match = useRouteMatch<{ id: string }>();

    const [ ips, setIPs ] = useState<Option[]>([]);
    const [ ports, setPorts ] = useState<Option[]>([]);

    useEffect(() => {
        getAllocations(match.params.id)
            .then(allocations => {
                setIPs(allocations.map(a => a.ip).filter(distinct).map(ip => {
                    return { value: ip, label: ip };
                }));
            });
    }, []);

    const onChange = (value: ValueType<Option, any>, action: ActionMeta<any>) => {
        console.log({
            event: 'onChange',
            value,
            action,
        });
    };

    const onInputChange = (newValue: string, actionMeta: InputActionMeta) => {
        console.log({
            event: 'onInputChange',
            newValue,
            actionMeta,
        });
    };

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const isValidNewOption1 = (inputValue: string, selectValue: ValueType<Option, any>, selectOptions: ReadonlyArray<Option | GroupTypeBase<Option>>): boolean => {
        return inputValue.match(/^([0-9a-f.:/]+)$/) !== null;
    };

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const isValidNewOption2 = (inputValue: string, selectValue: ValueType<Option, any>, selectOptions: ReadonlyArray<Option | GroupTypeBase<Option>>): boolean => {
        return inputValue.match(/^([0-9-]+)$/) !== null;
    };

    return (
        <AdminBox title={'Allocations'}>
            <div css={tw`mb-6`}>
                <Label>IPs and CIDRs</Label>
                <Creatable
                    options={ips}
                    styles={SelectStyle}
                    onChange={onChange}
                    onInputChange={onInputChange}
                    isValidNewOption={isValidNewOption1}
                    isMulti
                    isSearchable
                />
            </div>

            <div css={tw`mb-6`}>
                <Label>Ports</Label>
                <Creatable
                    options={ports}
                    styles={SelectStyle}
                    // onChange={onChange}
                    // onInputChange={onInputChange}
                    isValidNewOption={isValidNewOption2}
                    isMulti
                    isSearchable
                />
            </div>
        </AdminBox>
    );
};
