import { httpValidationErrors } from '@/api/http';
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

/** A node with no egg tags accepts every egg; otherwise it needs a tag in common with the egg. */
export const nodeAcceptsEgg = (nodeTags: AdminTag[], eggTags: AdminTag[]): boolean => {
    const eggTagIds = new Set(eggTags.map((tag) => tag.attributes.id));

    return nodeTags.length === 0 || nodeTags.some((tag) => eggTagIds.has(tag.attributes.id));
};

export const nodeRejectsEggMessage = (nodeName: string, nodeTags: AdminTag[]): string =>
    `${nodeName} only accepts eggs tagged ${nodeTags.map((tag) => tag.attributes.name).join(', ')}.`;

/** Submit meta of a form that can repeat its request with `ignore_tags`. */
export interface TagCheckMeta {
    ignoreTags: boolean;
}

/** The API's refusal of a node that does not accept the egg, which `ignore_tags` skips. */
export const tagCheckError = (cause: unknown, field: string): string | null => {
    const errors = httpValidationErrors(cause);

    return field in errors && errors[field].includes(' only accepts eggs tagged ') ? errors[field] : null;
};
