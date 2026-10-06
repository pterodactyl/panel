import React from 'react';
import features from './index';
import { getObjectKeys } from '@/lib/objects';

type ListItems = [string, React.ComponentType][];

export default function Features({ enabled }: { enabled: string[] }) {
    const enabledFeatures = new Set(enabled.map((value) => value.toLowerCase()));
    const mapped = getObjectKeys(features).flatMap((key): ListItems =>
        enabledFeatures.has(key.toLowerCase()) ? [[key, features[key]]] : []
    );

    return (
        <React.Suspense fallback={null}>
            {mapped.map(([key, Component]) => (
                <Component key={key} />
            ))}
        </React.Suspense>
    );
}
