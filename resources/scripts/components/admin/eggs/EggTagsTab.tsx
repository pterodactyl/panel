import { Link } from '@tanstack/react-router';
import { Tags } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { useAdminEgg } from '@/api/admin/eggs/queries';
import { syncAdminEggTagsInput, type AdminTag, useAllAdminTags, useSyncAdminEggTags } from '@/api/admin/tags/queries';
import TagsField from '@/components/admin/tags/TagsField';
import ContentBox from '@/components/elements/ContentBox';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import { Form, useAppForm } from '@/components/form';
import { eggDetailRoute } from '@/router/routeTree';

export default function EggTagsTab() {
    const loadedEgg = eggDetailRoute.useLoaderData();
    const eggId = loadedEgg.attributes.id;
    const { data: egg = loadedEgg, error: eggError, refetch } = useAdminEgg(eggId);
    const { data: tags = [], error: tagsError, refetch: refetchTags, isPending: tagsPending } = useAllAdminTags();
    const syncTags = useSyncAdminEggTags();
    const relationship = egg.attributes.relationships?.tags;
    const assignedTags: AdminTag[] = relationship && 'data' in relationship ? relationship.data : [];
    const form = useAppForm({
        defaultValues: { tags: assignedTags.map((tag) => String(tag.attributes.id)) },
        onSubmit: async ({ value }) => {
            try {
                await syncTags.mutateAsync(syncAdminEggTagsInput(eggId, value.tags));
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    if (eggError || tagsError) {
        return (
            <ServerError
                message={httpErrorToHuman(eggError ?? tagsError)}
                onRetry={() => {
                    if (eggError) {
                        void refetch();
                    }

                    if (tagsError) {
                        void refetchTags();
                    }
                }}
            />
        );
    }

    if (tagsPending) {
        return <Spinner size='large' centered />;
    }

    return (
        <ContentBox title='Egg Tags'>
            {tags.length === 0 ? (
                <Empty className={emptyCompactClass}>
                    <EmptyHeader>
                        <EmptyMedia variant='icon'>
                            <Tags />
                        </EmptyMedia>
                        <EmptyTitle>No tags yet</EmptyTitle>
                        <EmptyDescription>
                            Create a tag on the <Link to='/panel/tags'>Tags page</Link> before assigning one to this
                            egg.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <Form form={form} className='m-0'>
                    <form.AppField name='tags'>
                        {() => (
                            <TagsField
                                tags={tags}
                                label='Assigned Tags'
                                description='Tags organize eggs and match them with nodes configured to accept them.'
                                emptyText='No tags are assigned to this egg.'
                            />
                        )}
                    </form.AppField>
                    <div className='mt-6 flex justify-end'>
                        <form.AppForm>
                            <form.SubmitButton>Save tags</form.SubmitButton>
                        </form.AppForm>
                    </div>
                </Form>
            )}
        </ContentBox>
    );
}
