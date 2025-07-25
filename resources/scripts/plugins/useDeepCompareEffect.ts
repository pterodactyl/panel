import { DependencyList, EffectCallback, useEffect } from 'react';
import { useDeepMemoize } from './useDeepMemoize';

export const useDeepCompareEffect = (callback: EffectCallback, dependencies: DependencyList) =>
    useEffect(callback, useDeepMemoize(dependencies));
