import { describe, expect, it } from 'vitest';
import { mapListItems, removeListItems, updateListItems, upsertListItem } from '@/api/queryData';

type Item = { id: number; name: string };
type Response = {
    object: 'list';
    data: Item[];
    meta: { page: number };
};

const response = (data: Item[]): Response => ({ object: 'list', data, meta: { page: 2 } });

describe('query data list transforms', () => {
    it('leaves missing cache data untouched unless an upsert creator is provided', () => {
        expect(removeListItems<Response>(undefined, () => true)).toBeUndefined();
        expect(
            updateListItems<Response>(
                undefined,
                () => true,
                (item) => item
            )
        ).toBeUndefined();
        expect(mapListItems<Response>(undefined, (item) => item)).toBeUndefined();
        expect(upsertListItem<Response>(undefined, { id: 1, name: 'one' }, (item) => item.id === 1)).toBeUndefined();
        expect(
            upsertListItem<Response>(
                undefined,
                { id: 1, name: 'one' },
                (item) => item.id === 1,
                (item) => response([item])
            )
        ).toEqual(response([{ id: 1, name: 'one' }]));
    });

    it('inserts and replaces items without dropping response metadata', () => {
        const initial = response([{ id: 1, name: 'one' }]);
        const inserted = upsertListItem(initial, { id: 2, name: 'two' }, (item, next) => item.id === next.id);
        const replaced = upsertListItem(inserted, { id: 1, name: 'updated' }, (item, next) => item.id === next.id);

        expect(replaced).toEqual(
            response([
                { id: 1, name: 'updated' },
                { id: 2, name: 'two' },
            ])
        );
        expect(replaced?.meta).toEqual(initial.meta);
    });

    it('removes, updates, and maps matching items immutably', () => {
        const initial = response([
            { id: 1, name: 'one' },
            { id: 2, name: 'two' },
        ]);

        const removed = removeListItems(initial, (item) => item.id === 1);
        const updated = updateListItems(
            initial,
            (item) => item.id === 2,
            (item) => ({ ...item, name: 'updated' })
        );
        const mapped = mapListItems(initial, (item) => ({ ...item, name: item.name.toUpperCase() }));

        expect(removed).toEqual(response([{ id: 2, name: 'two' }]));
        expect(updated).toEqual(
            response([
                { id: 1, name: 'one' },
                { id: 2, name: 'updated' },
            ])
        );
        expect(mapped).toEqual(
            response([
                { id: 1, name: 'ONE' },
                { id: 2, name: 'TWO' },
            ])
        );
        expect(initial).toEqual(
            response([
                { id: 1, name: 'one' },
                { id: 2, name: 'two' },
            ])
        );
    });
});
