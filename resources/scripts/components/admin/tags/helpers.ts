import type { AdminTag } from '@/api/admin/tags/queries';

/**
 * The field value for a typed tag name: the id of the tag with that name or slug, otherwise
 * the name, which the sync endpoints turn into a new tag. The API reads a number as a tag id,
 * so a numeric name has no value.
 */
export const tagValueForName = (tags: AdminTag[], name: string): string | null => {
    const text = name.trim();
    const match = tags.find(({ attributes }) =>
        [attributes.name, attributes.slug].some((candidate) => candidate.toLowerCase() === text.toLowerCase())
    );

    if (match) {
        return String(match.attributes.id);
    }

    return text === '' || /^\d+$/.test(text) ? null : text;
};
