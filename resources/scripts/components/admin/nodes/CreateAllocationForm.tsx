import { Form, Formik, FormikHelpers } from 'formik';
import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { array, number, object, string } from 'yup';
import getAllocations from '@/api/admin/nodes/getAllocations';
import Button from '@/components/elements/Button';
import SelectField, { Option } from '@/components/elements/SelectField';

interface Values {
    ips: string[];
    ports: number[];
}

const distinct = (value: any, index: any, self: any) => {
    return self.indexOf(value) === index;
};

function CreateAllocationForm ({ nodeId }: { nodeId: string | number }) {
    const [ ips, setIPs ] = useState<Option[]>([]);
    const [ ports ] = useState<Option[]>([]);

    useEffect(() => {
        getAllocations(nodeId)
            .then(allocations => {
                setIPs(allocations.map(a => a.ip).filter(distinct).map(ip => {
                    return { value: ip, label: ip };
                }));
            });
    }, [ nodeId ]);

    const isValidIP = (inputValue: string): boolean => {
        // TODO: Better way of checking for a valid ip (and CIDR).
        return inputValue.match(/^([0-9a-f.:/]+)$/) !== null;
    };

    const isValidPort = (inputValue: string): boolean => {
        // TODO: Better way of checking for a valid port (and port range)
        return inputValue.match(/^([0-9-]+)$/) !== null;
    };

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        setSubmitting(false);
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                ips: [] as string[],
                ports: [] as number[],
            }}
            validationSchema={object().shape({
                ips: array(string()).min(1, 'You must select at least one ip address.'),
                ports: array(number()).min(1, 'You must select at least one port.'),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
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

                        <div css={tw`w-full flex flex-row items-center mt-6`}>
                            <div css={tw`flex ml-auto`}>
                                <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                    Create Allocations
                                </Button>
                            </div>
                        </div>
                    </Form>
                )
            }
        </Formik>
    );
}

export default CreateAllocationForm;
