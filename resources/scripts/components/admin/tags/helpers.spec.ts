import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it } from 'vitest';
import type { AdminTag } from '@/api/admin/tags/queries';
import { nodeAcceptsEgg, nodeRejectsEggMessage, tagCheckError, tagValueForName } from './helpers';

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

const srcds = tag(1, 'Source Engine', 'srcds');
const premium = tag(2, 'Premium', 'premium');
const minecraft = tag(3, 'Minecraft', 'minecraft');
const tags = [srcds, premium];

const unprocessable = (field: string, detail: string) =>
    new AxiosError('Unprocessable', '422', undefined, undefined, {
        status: 422,
        statusText: 'Unprocessable Content',
        headers: {},
        config: { headers: new AxiosHeaders() },
        data: { errors: [{ code: 'ValidationException', detail, meta: { source_field: field, rule: '' } }] },
    });

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

describe('nodeAcceptsEgg', () => {
    it('accepts every egg on a node without egg tags', () => {
        expect(nodeAcceptsEgg([], [])).toBe(true);
        expect(nodeAcceptsEgg([], [minecraft])).toBe(true);
    });

    it('needs one tag in common with a tagged node', () => {
        expect(nodeAcceptsEgg([minecraft, srcds], [srcds, premium])).toBe(true);
        expect(nodeAcceptsEgg([minecraft], [srcds, premium])).toBe(false);
        expect(nodeAcceptsEgg([minecraft], [])).toBe(false);
    });

    it('words a refusal the way the API does', () => {
        expect(nodeRejectsEggMessage('node-alpha', [minecraft, srcds])).toBe(
            'node-alpha only accepts eggs tagged Minecraft, Source Engine.'
        );
    });
});

describe('tagCheckError', () => {
    it('returns the tag check message for the field', () => {
        const message = 'node-alpha only accepts eggs tagged Minecraft.';

        expect(tagCheckError(unprocessable('node_id', message), 'node_id')).toBe(message);
    });

    it('ignores other validation errors and other fields', () => {
        expect(tagCheckError(unprocessable('node_id', 'The node id field is required.'), 'node_id')).toBeNull();
        expect(tagCheckError(unprocessable('egg_id', 'n only accepts eggs tagged A.'), 'node_id')).toBeNull();
        expect(tagCheckError(new Error('offline'), 'node_id')).toBeNull();
    });
});
