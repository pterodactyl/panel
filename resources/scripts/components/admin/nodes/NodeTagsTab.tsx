import { useSuspenseQuery } from '@tanstack/react-query';
import { useStore } from '@tanstack/react-form';
import { httpErrorToHuman } from '@/api/http';
import { adminNodeTagsQueryOptions } from '@/api/admin/nodes/queries';
import {
    type AdminTag,
    syncAdminNodeDeploymentTagsInput,
    syncAdminNodeGameTagsInput,
    useAllAdminTags,
    useSyncAdminNodeDeploymentTags,
    useSyncAdminNodeGameTags,
} from '@/api/admin/tags/queries';
import { relationshipData } from '@/api/relationships';
import NoTagsYet from '@/components/admin/tags/NoTagsYet';
import TagsField from '@/components/admin/tags/TagsField';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import { Form, useAppForm } from '@/components/form';
import { nodeDetailRoute } from '@/router/routeTree';

const tagIds = (tags: AdminTag[]): string[] => tags.map((tag) => String(tag.attributes.id));

export default function NodeTagsTab() {
    const { id } = nodeDetailRoute.useParams();
    const { data: node } = useSuspenseQuery(adminNodeTagsQueryOptions(id));
    const { data: tags = [], error, refetch, isPending } = useAllAdminTags();
    const syncGames = useSyncAdminNodeGameTags();
    const syncReservations = useSyncAdminNodeDeploymentTags();
    const reserved = tagIds(relationshipData(node.attributes.relationships?.deployment_tags));
    const form = useAppForm({
        defaultValues: {
            games: tagIds(relationshipData(node.attributes.relationships?.tags)),
            reservations: reserved,
        },
        onSubmit: async ({ value }) => {
            // A node holds a tag as one kind at a time, so a reserved tag only becomes a game
            // once the reservations have been saved without it.
            const moving = value.games.filter((tag) => reserved.includes(tag));

            try {
                let savedGames = await syncGames.mutateAsync(
                    syncAdminNodeGameTagsInput(
                        id,
                        value.games.filter((tag) => !moving.includes(tag))
                    )
                );
                const savedReservations = await syncReservations.mutateAsync(
                    syncAdminNodeDeploymentTagsInput(id, value.reservations)
                );

                if (moving.length > 0) {
                    savedGames = await syncGames.mutateAsync(syncAdminNodeGameTagsInput(id, value.games));
                }

                form.reset({ games: tagIds(savedGames.data), reservations: tagIds(savedReservations.data) });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const games = useStore(form.store, (state) => state.values.games);
    const reservations = useStore(form.store, (state) => state.values.reservations);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => void refetch()} />;
    }

    if (isPending) {
        return <Spinner size='large' centered />;
    }

    return (
        <TitledGreyBox title='Node Tags'>
            {tags.length === 0 && <NoTagsYet />}
            <Form form={form} className='m-0'>
                <div className='grid grid-cols-1 gap-6 md:grid-cols-2'>
                    <div>
                        <form.AppField name='games'>
                            {() => (
                                <TagsField
                                    tags={tags}
                                    exclude={reservations}
                                    label='Games'
                                    description='An egg runs here when it has one of these tags. Leave this empty to accept every egg.'
                                    emptyText='This node accepts every egg.'
                                />
                            )}
                        </form.AppField>
                    </div>
                    <div>
                        <form.AppField name='reservations'>
                            {() => (
                                <TagsField
                                    tags={tags}
                                    exclude={games}
                                    label='Reservations'
                                    description='A reserved node only takes deployments that name every one of these tags.'
                                    emptyText='This node is not reserved.'
                                />
                            )}
                        </form.AppField>
                    </div>
                </div>
                <div className='mt-6 flex justify-end'>
                    <form.AppForm>
                        <form.SubmitButton>Save tags</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </TitledGreyBox>
    );
}
