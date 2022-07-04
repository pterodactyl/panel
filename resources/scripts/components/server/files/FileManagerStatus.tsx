import React from 'react';
import tw, { styled } from 'twin.macro';
import { ServerContext } from '@/state/server';

const SpinnerCircle = styled.circle`
    transition: stroke-dashoffset 0.35s;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
`;

function Spinner({ progress }: { progress: number }) {
    const stroke = 3;
    const radius = 20;
    const normalizedRadius = radius - stroke * 2;
    const circumference = normalizedRadius * 2 * Math.PI;

    return (
        <svg width={radius * 2 - 8} height={radius * 2 - 8}>
            <circle
                stroke={'rgba(255, 255, 255, 0.07)'}
                fill={'none'}
                strokeWidth={stroke}
                r={normalizedRadius}
                cx={radius - 4}
                cy={radius - 4}
            />
            <SpinnerCircle
                stroke={'white'}
                fill={'none'}
                strokeDasharray={circumference}
                strokeWidth={stroke}
                r={normalizedRadius}
                cx={radius - 4}
                cy={radius - 4}
                style={{ strokeDashoffset: ((100 - progress) / 100) * circumference }}
            />
        </svg>
    );
}

function FileManagerStatus() {
    const uploads = ServerContext.useStoreState((state) => state.files.uploads);

    return (
        <div css={tw`pointer-events-none fixed right-0 bottom-0 z-20 flex justify-center`}>
            {uploads.length > 0 && (
                <div
                    css={tw`flex flex-col justify-center bg-neutral-700 rounded shadow mb-2 mr-2 pointer-events-auto px-3 py-1`}
                >
                    {uploads
                        .sort((a, b) => a.total - b.total)
                        .map((f) => (
                            <div key={f.name} css={tw`h-10 flex flex-row items-center`}>
                                <div css={tw`mr-2`}>
                                    <Spinner progress={Math.round((100 * f.loaded) / f.total)} />
                                </div>

                                <div css={tw`block`}>
                                    <span css={tw`text-base font-normal leading-none text-neutral-300`}>{f.name}</span>
                                </div>
                            </div>
                        ))}
                </div>
            )}
        </div>
    );
}

export default FileManagerStatus;
