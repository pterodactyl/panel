import { DependencyList, useMemo } from 'react';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';

export const useDeepCompareMemo = <T>(callback: () => T, dependencies: DependencyList) =>
    useMemo(callback, useDeepMemoize(dependencies));
