import { useDeepMemoize } from './useDeepMemoize';
import { DependencyList, EffectCallback, useEffect } from 'react';

export const useDeepCompareEffect = (callback: EffectCallback, dependencies: DependencyList) =>
    useEffect(callback, useDeepMemoize(dependencies));
