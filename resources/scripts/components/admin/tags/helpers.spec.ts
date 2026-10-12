import { describe, expect, it } from 'vitest';
import type { AdminTag } from '@/api/admin/tags/queries';
import { tagValueForName } from './helpers';

const tag = (id: number, name: string, slug: string): AdminTag => ({
    object: 'tag',
    attributes: {
        id,
        name,
        slug,
        color: null,
        is_predefined: false,
        legacy_nest_id: null,
        eggs_count: 0,
        nodes_count: 0,
        created_at: '',
        updated_at: '',
    },
});

const tags = [tag(1, 'Source Engine', 'srcds'), tag(2, 'Premium', 'premium')];

describe('tagValueForName', () => {
    it('picks the existing tag a name or slug refers to, ignoring case and spacing', () => {
        expect(tagValueForName(tags, ' source engine ')).toBe('1');
        expect(tagValueForName(tags, 'SRCDS')).toBe('1');
        expect(tagValueForName(tags, 'premium')).toBe('2');
    });

    it('keeps any other name for the API to create', () => {
        expect(tagValueForName(tags, '  Survival ')).toBe('Survival');
    });

    it('has no value for an empty or numeric name, which the API reads as a tag id', () => {
        expect(tagValueForName(tags, '   ')).toBeNull();
        expect(tagValueForName(tags, '42')).toBeNull();
    });
});
