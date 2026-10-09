import { Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { createAdminNodeInput, useCreateAdminNode } from '@/api/admin/nodes/queries';
import NodeFormFields from '@/components/admin/nodes/NodeFormFields';
import { newNodeFormValues, nodeValuesFromForm } from '@/components/admin/nodes/nodeForm';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ExtensionFormFields from '@/components/admin/extensions/ExtensionFormFields';
import { useExtensionPayload } from '@/extensions/forms';
import Icon from '@/components/elements/Icon';
import { useAppForm, Form } from '@/components/form';

export default function CreateNodeForm() {
    const navigate = useNavigate();
    const createNode = useCreateAdminNode();
    const requiresSslScheme = globalThis.window?.location.protocol === 'https:';

    const { withExtensionPayload } = useExtensionPayload('admin.node');
    const form = useAppForm({
        defaultValues: newNodeFormValues(),
        onSubmit: async ({ value }) => {
            try {
                const node = await createNode.mutateAsync(
                    createAdminNodeInput(nodeValuesFromForm(withExtensionPayload(value)))
                );

                void navigate({ to: '/panel/nodes/$id/allocation', params: { id: node.attributes.id } });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <AdminContentBlock
            title='Admin · Create Node'
            heading='Create Node'
            description='Add a Wings node to your panel.'
        >
            <Link
                to='/panel/nodes'
                className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                Back to Nodes
            </Link>
            <Form form={form}>
                <div className='space-y-6'>
                    <NodeFormFields form={form} prefix='create_' requiresSslScheme={requiresSslScheme} />
                    <form.AppField name='extensions'>
                        {() => <ExtensionFormFields form='admin.node' mode='create' error={createNode.error} boxed />}
                    </form.AppField>
                    <div className='flex justify-end'>
                        <form.AppForm>
                            <form.SubmitButton>Create Node</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </div>
            </Form>
        </AdminContentBlock>
    );
}
