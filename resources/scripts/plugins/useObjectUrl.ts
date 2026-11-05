import { useEffect, useState } from 'react';

/** A URL for previewing a picked file, revoked once the file changes or the component unmounts. */
export function useObjectUrl(file: File | null): string | null {
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setUrl(null);

            return;
        }

        const created = URL.createObjectURL(file);

        setUrl(created);

        return () => URL.revokeObjectURL(created);
    }, [file]);

    return url;
}
