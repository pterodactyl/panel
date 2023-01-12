import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import { useFormikContext } from 'formik';
import tw from 'twin.macro';

import getAllocations from '@/api/admin/nodes/getAllocations';
import { useServerFromRoute } from '@/api/admin/server';
import AdminBox from '@/components/admin/AdminBox';
import Label from '@/components/elements/Label';
import Select from '@/components/elements/Select';
import type { Option } from '@/components/elements/SelectField';
import SelectField, { AsyncSelectField } from '@/components/elements/SelectField';

export default () => {
    const { isSubmitting } = useFormikContext();
    const { data: server } = useServerFromRoute();

    const loadOptions = async (inputValue: string, callback: (options: Option[]) => void) => {
        if (!server) {
            callback([] as Option[]);
            return;
        }

        const allocations = await getAllocations(server.nodeId, { ip: inputValue, server_id: '0' });

        callback(
            allocations.map(a => {
                return { value: a.id.toString(), label: a.getDisplayText() };
            }),
        );
    };

    return (
        <AdminBox icon={faNetworkWired} title={'Networking'} isLoading={isSubmitting}>
            <div css={tw`grid grid-cols-1 gap-4 lg:gap-6`}>
                <div>
                    <Label htmlFor={'allocationId'}>Primary Allocation</Label>
                    <Select id={'allocationId'} name={'allocationId'}>
                        {server?.relationships.allocations?.map(a => (
                            <option key={a.id} value={a.id}>
                                {a.getDisplayText()}
                            </option>
                        ))}
                    </Select>
                </div>
                <AsyncSelectField
                    id={'addAllocations'}
                    name={'addAllocations'}
                    label={'Add Allocations'}
                    loadOptions={loadOptions}
                    isMulti
                />
                <SelectField
                    id={'removeAllocations'}
                    name={'removeAllocations'}
                    label={'Remove Allocations'}
                    options={
                        server?.relationships.allocations?.map(a => {
                            return { value: a.id.toString(), label: a.getDisplayText() };
                        }) || []
                    }
                    isMulti
                    isSearchable
                />
            </div>
        </AdminBox>
    );
};
