import { isNotFound } from '@tanstack/react-router';
import { describe, expect, it } from 'vitest';
import { eggIdParam, parseRouteId, routeParamStrings } from './params';

const thrown = (run: () => unknown): unknown => {
    try {
        run();
    } catch (error) {
        return error;
    }

    return undefined;
};

describe('route params', () => {
    it('parses positive integer ids', () => {
        expect(parseRouteId('1')).toBe(1);
        expect(parseRouteId('42')).toBe(42);
        expect(eggIdParam.parse({ eggId: '7' })).toEqual({ eggId: 7 });
        expect(eggIdParam.stringify({ eggId: 7 })).toEqual({ eggId: '7' });
    });

    it.each(['0', '-1', '1.5', 'abc', '', '01', '1e3', '9007199254740993'])('treats %j as not found', (value) => {
        expect(isNotFound(thrown(() => parseRouteId(value)))).toBe(true);
    });

    it('presents every param value as its path segment text', () => {
        expect(routeParamStrings({ id: 42, action: 'edit', missing: undefined })).toEqual({ id: '42', action: 'edit' });
    });
});
