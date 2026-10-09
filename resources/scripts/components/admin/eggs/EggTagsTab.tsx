import { Link } from '@tanstack/react-router';
import { useStore } from '@tanstack/react-form';
import { Tags } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { useAdminEgg } from '@/api/admin/eggs/queries';
import { syncAdminEggTagsInput, type AdminTag, useAllAdminTags, useSyncAdminEggTags } from '@/api/admin/tags/queries';
import TagBadge from '@/components/admin/tags/TagBadge';
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
    const selectedTagIds = useStore(form.store, (state) => state.values.tags);
    const selectedTagIdSet = new Set(selectedTagIds);
    const selectedTags = tags.filter((tag) => selectedTagIdSet.has(String(tag.attributes.id)));

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
                        {(field) => (
                            <field.MultiSelectField
                                label='Assigned Tags'
                                description='Tags organize eggs and match them with nodes configured to accept them.'
                                placeholder='Select tags'
                                searchPlaceholder='Search tags…'
                                options={tags.map((tag) => ({
                                    value: String(tag.attributes.id),
                                    label: `${tag.attributes.name} (${tag.attributes.slug})`,
                                }))}
                            />
                        )}
                    </form.AppField>
                    <div className='mt-4 min-h-8'>
                        {selectedTags.length > 0 ? (
                            <div className='flex flex-wrap gap-2'>
                                {selectedTags.map((tag) => (
                                    <TagBadge key={tag.attributes.id} tag={tag} showSlug />
                                ))}
                            </div>
                        ) : (
                            <p className='text-sm text-muted-foreground'>No tags are assigned to this egg.</p>
                        )}
                    </div>
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
