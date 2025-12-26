import React, { useEffect, useState } from 'react';
import { Dialog } from '@/components/elements/dialog';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import { httpErrorToHuman } from '@/api/http';
import http from '@/api/http';
import Spinner from '@/components/elements/Spinner';
import asDialog from '@/hoc/asDialog';

interface Props {
    file: string;
}

const ImagePreviewContent = ({ file }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [imageUrl, setImageUrl] = useState<string | null>(null);

    useEffect(() => {
        setLoading(true);
        setError(null);

        http.get(`/api/client/servers/${uuid}/files/contents`, {
            params: { file },
            responseType: 'blob',
        })
            .then((response) => {
                const ext = file.split('.').pop()?.toLowerCase();
                let mimeType = 'image/png';

                if (ext === 'jpg' || ext === 'jpeg') mimeType = 'image/jpeg';
                else if (ext === 'gif') mimeType = 'image/gif';
                else if (ext === 'svg') mimeType = 'image/svg+xml';
                else if (ext === 'webp') mimeType = 'image/webp';
                else if (ext === 'bmp') mimeType = 'image/bmp';
                else if (ext === 'ico') mimeType = 'image/x-icon';

                const blob = new Blob([response.data], { type: mimeType });
                const url = URL.createObjectURL(blob);
                setImageUrl(url);
                setLoading(false);
            })
            .catch((err) => {
                console.error(err);
                setError(httpErrorToHuman(err));
                setLoading(false);
            });

        return () => {
            if (imageUrl) {
                URL.revokeObjectURL(imageUrl);
            }
        };
    }, [file, uuid]);

    if (loading) {
        return (
            <div css={tw`flex items-center justify-center py-12`}>
                <Spinner size="large" />
            </div>
        );
    }

    if (error) {
        return (
            <div css={tw`p-4 bg-red-500/10 border border-red-500 rounded text-red-400 text-sm`}>
                <p css={tw`font-semibold mb-1`}>Error loading image</p>
                <p>{error}</p>
            </div>
        );
    }

    if (!imageUrl) {
        return null;
    }

    return (
        <div css={tw`flex flex-col items-center mt-4`}>
            <p css={tw`text-neutral-400 text-xs mb-4 truncate max-w-full font-mono px-2`}>{file}</p>
            <div css={tw`w-full flex justify-center items-center bg-neutral-900 rounded-lg p-6`}>
                <img
                    src={imageUrl}
                    alt={file}
                    css={tw`max-w-full h-auto rounded shadow-lg`}
                    style={{ maxHeight: '70vh' }}
                />
            </div>
        </div>
    );
};

const ImagePreviewModal = asDialog({
    title: 'Image Preview',
})(ImagePreviewContent);

export default ImagePreviewModal;