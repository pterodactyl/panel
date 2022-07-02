import { useLocation } from 'react-router';
import { useMemo } from 'react';

export default () => {
    const location = useLocation();

    const getHashObject = (value: string): Record<string, string> =>
        value
            .substring(1)
            .split('&')
            .reduce((obj, str) => {
                const [key, value = ''] = str.split('=');

                return !str.trim() ? obj : { ...obj, [key]: value };
            }, {});

    const pathTo = (params: Record<string, string>): string => {
        const current = getHashObject(location.hash);

        for (const key in params) {
            current[key] = params[key];
        }

        return Object.keys(current)
            .map((key) => `${key}=${current[key]}`)
            .join('&');
    };

    const hash = useMemo((): Record<string, string> => getHashObject(location.hash), [location.hash]);

    return { hash, pathTo };
};
