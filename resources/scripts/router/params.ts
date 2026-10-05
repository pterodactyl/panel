import { notFound, useMatch } from '@tanstack/react-router';

/** Throws notFound() for anything but a positive integer. */
export const parseRouteId = (value: string): number => {
    const id = /^[1-9]\d*$/.test(value) ? Number(value) : Number.NaN;

    if (!Number.isSafeInteger(id)) {
        throw notFound();
    }

    return id;
};

export const idParam = {
    parse: ({ id }: { id: string }) => ({ id: parseRouteId(id) }),
    stringify: ({ id }: { id: number }) => ({ id: String(id) }),
};

export const eggIdParam = {
    parse: ({ eggId }: { eggId: string }) => ({ eggId: parseRouteId(eggId) }),
    stringify: ({ eggId }: { eggId: number }) => ({ eggId: String(eggId) }),
};

export const scheduleIdParam = {
    parse: ({ scheduleId }: { scheduleId: string }) => ({ scheduleId: parseRouteId(scheduleId) }),
    stringify: ({ scheduleId }: { scheduleId: number }) => ({ scheduleId: String(scheduleId) }),
};

export const routeParamStrings = (
    params: Readonly<Record<string, string | number | undefined>>
): Record<string, string> =>
    Object.fromEntries(
        Object.entries(params).flatMap(([name, value]) => (value === undefined ? [] : [[name, String(value)]]))
    );

/** Undefined outside the `/server/$id` route. */
export const useServerRouteId = (): string | undefined =>
    useMatch({ from: '/authenticated/server/$id', shouldThrow: false, select: (match): string => match.params.id });
