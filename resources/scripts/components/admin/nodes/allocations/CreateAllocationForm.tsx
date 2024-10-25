import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { array, number, object, string } from 'yup';

import createAllocation from '@/api/admin/nodes/allocations/createAllocation';
import getAllocations from '@/api/admin/nodes/getAllocations';
import getAllocations2 from '@/api/admin/nodes/allocations/getAllocations';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import type { Option } from '@/components/elements/SelectField';
import SelectField from '@/components/elements/SelectField';

interface Values {
    ips: string[];
    ports: number[];
    alias: string;
}

const distinct = (value: any, index: any, self: any) => {
    return self.indexOf(value) === index;
};

function CreateAllocationForm({ nodeId }: { nodeId: number }) {
    const [ips, setIPs] = useState<Option[]>([]);
    const [ports] = useState<Option[]>([]);

    const { mutate } = getAllocations2(nodeId, ['server']);

    useEffect(() => {
        getAllocations(nodeId).then(allocations => {
            setIPs(
                allocations
                    .map(a => a.ip)
                    .filter(distinct)
                    .map(ip => {
                        return { value: ip, label: ip };
                    }),
            );
        });
    }, [nodeId]);

    const isValidIP = (inputValue: string): boolean => {
        // TODO: Better way of checking for a valid ip (and CIDR)
        return inputValue.match(/^([0-9a-f.:/]+)$/) !== null;
    };

    const isValidPort = (inputValue: string): boolean => {
        // TODO: Better way of checking for a valid port (and port range)
        return inputValue.match(/^([0-9-]+)$/) !== null;
    };

    const submit = ({ ips, ports, alias }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        setSubmitting(false);

        ips.forEach(async ip => {
            const allocations = await createAllocation(nodeId, { ip, ports, alias }, ['server']);
            await mutate(data => ({ ...data!, items: { ...data!.items!, ...allocations } }));
        });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                ips: [] as string[],
                ports: [] as number[],
                alias: '',
            }}
            validationSchema={object().shape({
                ips: array(string()).min(1, 'You must select at least one ip address.'),
                ports: array(number()).min(1, 'You must select at least one port.'),
            })}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <SelectField
                        id={'ips'}
                        name={'ips'}
                        label={'IPs and CIDRs'}
                        options={ips}
                        isValidNewOption={isValidIP}
                        isMulti
                        isSearchable
                        isCreatable
                        css={tw`mb-6`}
                    />

                    <SelectField
                        id={'ports'}
                        name={'ports'}
                        label={'Ports'}
                        options={ports}
                        isValidNewOption={isValidPort}
                        isMulti
                        isSearchable
                        isCreatable
                    />

                    <div css={tw`mt-6`}>
                        <Field id={'alias'} name={'alias'} label={'Alias'} type={'text'} />
                    </div>

                    <div css={tw`w-full flex flex-row items-center mt-6`}>
                        <div css={tw`flex ml-auto`}>
                            <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                Create Allocations
                            </Button>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
}

export default CreateAllocationForm;
