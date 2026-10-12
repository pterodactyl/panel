import type { AdminTag } from '@/api/admin/tags/queries';
import TagBadge from '@/components/admin/tags/TagBadge';
import { useFieldContext } from '@/components/form';
import { MultiSelectField } from '@/components/form/fields';

interface Props {
    tags: AdminTag[];
    label: string;
    description: string;
    emptyText: string;
    /** Tag ids that are taken elsewhere and must not be offered here. */
    exclude?: string[];
}

/** A `string[]` field of tag ids, with the selected tags shown as badges. */
export default function TagsField({ tags, label, description, emptyText, exclude }: Props) {
    const field = useFieldContext<string[]>();
    const selected = new Set(field.state.value);
    const excluded = new Set(exclude);
    const selectedTags = tags.filter((tag) => selected.has(String(tag.attributes.id)));

    return (
        <>
            <MultiSelectField
                label={label}
                description={description}
                placeholder='Select tags'
                searchPlaceholder='Search tags…'
                options={tags
                    .filter((tag) => !excluded.has(String(tag.attributes.id)))
                    .map((tag) => ({
                        value: String(tag.attributes.id),
                        label: `${tag.attributes.name} (${tag.attributes.slug})`,
                    }))}
            />
            <div className='mt-4 min-h-8'>
                {selectedTags.length > 0 ? (
                    <div className='flex flex-wrap gap-2'>
                        {selectedTags.map((tag) => (
                            <TagBadge key={tag.attributes.id} tag={tag} showSlug />
                        ))}
                    </div>
                ) : (
                    <p className='text-sm text-muted-foreground'>{emptyText}</p>
                )}
            </div>
        </>
    );
}
