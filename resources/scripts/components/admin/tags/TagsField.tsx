import { toast } from 'sonner';
import type { AdminTag } from '@/api/admin/tags/queries';
import TagBadge from '@/components/admin/tags/TagBadge';
import { tagValueForName } from '@/components/admin/tags/helpers';
import { useFieldContext } from '@/components/form';
import { MultiSelectField } from '@/components/form/fields';
import { useTranslation } from '@/sdk/localization';

interface Props {
    tags: AdminTag[];
    label: string;
    description: string;
    emptyText: string;
    /** Tag ids that are taken elsewhere and must not be offered here. */
    exclude?: string[];
}

/** A `string[]` field of tag ids and new tag names, with the selection shown as badges. */
export default function TagsField({ tags, label, description, emptyText, exclude }: Props) {
    const { t } = useTranslation();
    const field = useFieldContext<string[]>();
    const values = field.state.value;
    const excluded = new Set(exclude);
    const byId = new Map(tags.map((tag) => [String(tag.attributes.id), tag]));
    const newNames = values.filter((value) => !byId.has(value));

    const create = (name: string) => {
        const value = tagValueForName(tags, name);

        if (value === null) {
            toast.error(t('A tag name cannot be only a number.'));
        } else if (excluded.has(value)) {
            toast.error(t('{{name}} is already in the other list.', { name }));
        } else if (!values.includes(value)) {
            field.handleChange([...values, value]);
        }
    };

    return (
        <>
            <MultiSelectField
                label={label}
                description={description}
                placeholder={t('Select or type tags')}
                searchPlaceholder={t('Search or create tags…')}
                onCreate={create}
                options={[
                    ...tags
                        .filter((tag) => !excluded.has(String(tag.attributes.id)))
                        .map((tag) => ({
                            value: String(tag.attributes.id),
                            label: `${tag.attributes.name} (${tag.attributes.slug})`,
                        })),
                    ...newNames.map((name) => ({ value: name, label: t('{{name}} (new)', { name }) })),
                ]}
            />
            <div className='mt-4 min-h-8'>
                {values.length > 0 ? (
                    <div className='flex flex-wrap gap-2'>
                        {values.map((value) => (
                            <TagBadge key={value} tag={byId.get(value) ?? value} showSlug />
                        ))}
                    </div>
                ) : (
                    <p className='text-sm text-muted-foreground'>{emptyText}</p>
                )}
            </div>
        </>
    );
}
