import type { ComponentType } from 'react';
import { Suspense, useMemo } from 'react';

import features from './index';
import { getObjectKeys } from '@/lib/objects';

type ListItems = [string, ComponentType][];

export default ({ enabled }: { enabled: string[] }) => {
    const mapped: ListItems = useMemo(() => {
        return getObjectKeys(features)
            .filter(key => enabled.map(v => v.toLowerCase()).includes(key.toLowerCase()))
            .reduce((arr, key) => [...arr, [key, features[key]]] as ListItems, [] as ListItems);
    }, [enabled]);

    return (
        <Suspense fallback={null}>
            {mapped.map(([key, Component]) => (
                <Component key={key} />
            ))}
        </Suspense>
    );
};
