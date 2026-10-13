import { useState } from 'react';
import { useStore } from '@tanstack/react-form';
import { toast } from 'sonner';
import { useAppForm, Form } from '@/components/form';
import Select, { type SelectGroup } from '@/components/ui/Select';
import { type LocationWithNodes, useAdminNodesGroupedByLocation, useAllAdminNodeTags } from '@/api/admin/nodes/queries';
import {
    type AdminServer,
    transferAdminServerInput,
    useAdminEggForServer,
    useAdminServerUnassignedAllocations,
    useTransferAdminServer,
} from '@/api/admin/servers/queries';
import { relationshipData } from '@/api/relationships';
import { allocationLabel, transferServerBodyFromFormValues } from '@/components/admin/servers/helpers';
import TagCheckAlert from '@/components/admin/tags/TagCheckAlert';
import { nodeAcceptsEgg, tagCheckError } from '@/components/admin/tags/helpers';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Label from '@/components/elements/Label';
import Button from '@/components/elements/Button';

interface Props {
    server: AdminServer;
    open: boolean;
    onClose: () => void;
}

interface Values {
    allocationId: number;
    allocationAdditional: number[];
}

/** Whether a node accepts the egg; every node does until the tags have loaded. */
const useNodeAcceptsEgg = (eggId: number, enabled: boolean) => {
    const { data: nodes } = useAllAdminNodeTags({ enabled });
    const { data: egg } = useAdminEggForServer(eggId, { enabled });
    const eggTags = relationshipData(egg?.attributes.relationships?.tags);
    const nodeTags = new Map(
        (nodes?.data ?? []).map((node) => [node.attributes.id, relationshipData(node.attributes.relationships?.tags)])
    );

    return (nodeId: number) => !egg || nodeAcceptsEgg(nodeTags.get(nodeId) ?? [], eggTags);
};

const transferNodeGroups = (
    groups: LocationWithNodes[],
    currentNodeId: number,
    acceptsEgg: (nodeId: number) => boolean
): SelectGroup[] =>
    groups.flatMap(({ location, nodes }) => {
        const options = nodes
            .filter((node) => node.id !== currentNodeId)
            .map((node) => ({
                value: node.id,
                label: acceptsEgg(node.id) ? node.name : `${node.name} (does not accept this egg)`,
            }));
        const { long, short } = location.attributes;

        return options.length > 0 ? [{ label: long ? `${long} (${short})` : short, options }] : [];
    });

export default function TransferServerModal({ server, open, onClose }: Props) {
    const { attributes } = server;
    const transferServer = useTransferAdminServer();

    const [nodeId, setNodeId] = useState(0);
    const [rejection, setRejection] = useState<string | null>(null);

    const { data: groups = [] } = useAdminNodesGroupedByLocation({
        enabled: open,
    });
    const acceptsEgg = useNodeAcceptsEgg(attributes.egg, open);

    const { data: allocationsResponse } = useAdminServerUnassignedAllocations(nodeId, {
        enabled: open && nodeId > 0,
    });
    const allocations = allocationsResponse?.data ?? [];

    const form = useAppForm({
        defaultValues: { allocationId: 0, allocationAdditional: [] } as Values,
        onSubmitMeta: { ignoreTags: false },
        onSubmit: async ({ value, meta }) => {
            if (!nodeId) {
                toast.error('A target node must be selected.');

                return;
            }

            if (!value.allocationId) {
                toast.error('A default allocation must be selected.');

                return;
            }

            try {
                await transferServer.mutateAsync(
                    transferAdminServerInput(attributes.id, {
                        ...transferServerBodyFromFormValues({
                            nodeId: Number(nodeId),
                            allocationId: Number(value.allocationId),
                            allocationAdditional: value.allocationAdditional.map(Number),
                        }),
                        ignore_tags: meta.ignoreTags,
                    })
                );
                setRejection(null);
                onClose();
            } catch (error) {
                setRejection(tagCheckError(error, 'node_id'));
            }
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);
    const defaultAllocationId = useStore(form.store, (state) => state.values.allocationId);

    const allocationOptions = allocations.map((allocation) => ({
        value: allocation.attributes.id,
        label: allocationLabel(allocation),
    }));

    const additionalOptions = allocationOptions.filter((option) => option.value !== defaultAllocationId);

    return (
        <Dialog
            open={open}
            title='Transfer server'
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className='m-0'>
                <Label htmlFor='nodeId'>Node</Label>
                <Select
                    id='nodeId'
                    value={nodeId}
                    placeholder='Select a target node...'
                    groups={transferNodeGroups(groups, attributes.node, acceptsEgg)}
                    onChange={(value) => {
                        setNodeId(Number(value));
                        setRejection(null);
                        form.setFieldValue('allocationId', 0);
                        form.setFieldValue('allocationAdditional', []);
                    }}
                />
                <p className='mt-1 text-xs text-muted-foreground'>The node this server will be transferred to.</p>
                <div className='mt-6'>
                    <form.AppField name='allocationId'>
                        {(field) => (
                            <field.SelectField
                                id='allocationId'
                                label='Default Allocation'
                                description='The main allocation that will be assigned to this server.'
                                options={[
                                    { value: 0, label: 'Select an allocation...', disabled: true },
                                    ...allocationOptions,
                                ]}
                                onChange={(value) =>
                                    form.setFieldValue('allocationAdditional', (current) =>
                                        current.filter((id) => id !== Number(value))
                                    )
                                }
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='mt-6'>
                    <form.AppField name='allocationAdditional'>
                        {(field) => (
                            <field.MultiSelectField
                                id='allocationAdditional'
                                label='Additional Allocation(s)'
                                description='Additional allocations to assign to this server.'
                                options={additionalOptions}
                            />
                        )}
                    </form.AppField>
                </div>
                {rejection && (
                    <div className='mt-6'>
                        <TagCheckAlert
                            message={rejection}
                            action='Transfer anyway'
                            pending={isSubmitting}
                            onAction={() => void form.handleSubmit({ ignoreTags: true })}
                        />
                    </div>
                )}
                <div className='flex flex-wrap justify-end mt-6'>
                    <Button type='button' isSecondary className='w-full sm:w-auto sm:mr-2' onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        className='w-full mt-4 sm:w-auto sm:mt-0'
                        type='button'
                        disabled={isSubmitting}
                        onClick={() => form.handleSubmit()}
                    >
                        Confirm Transfer
                    </Button>
                </div>
            </Form>
        </Dialog>
    );
}
