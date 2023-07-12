import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import tw from 'twin.macro';

import type { VersionData } from '@/api/admin/getVersion';
import getVersion from '@/api/admin/getVersion';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';

const Code = ({ children }: { children: ReactNode }) => {
    return (
        <code css={tw`text-sm font-mono bg-neutral-900 rounded`} style={{ padding: '2px 6px' }}>
            {children}
        </code>
    );
};

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [loading, setLoading] = useState<boolean>(true);
    const [versionData, setVersionData] = useState<VersionData | undefined>(undefined);

    useEffect(() => {
        clearFlashes('overview');

        getVersion()
            .then(versionData => setVersionData(versionData))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'overview', error });
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <AdminContentBlock title={'Overview'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Overview</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        A quick glance at your system.
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'overview'} css={tw`mb-4`} />

            <div css={tw`flex flex-col w-full rounded-lg shadow-md bg-neutral-700`}>
                {loading ? (
                    <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '16rem' }}>
                        <Spinner size={'base'} />
                    </div>
                ) : (
                    <div css={tw`rounded shadow-md bg-neutral-700`}>
                        <div css={tw`bg-neutral-900 rounded-t border-b border-black px-4 py-3`}>
                            <p css={tw`text-sm uppercase`}>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    css={tw`inline-block mr-2`}
                                    style={{ height: '1rem' }}
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
                                    />
                                </svg>
                                System Information
                            </p>
                        </div>

                        <div css={tw`px-4 py-4`}>
                            {versionData?.panel.current === 'canary' ? (
                                <p css={tw`text-neutral-200`}>
                                    I hope you enjoy living on the edge because you are running a{' '}
                                    <Code>{versionData?.panel.current}</Code> version of Pterodactyl.
                                </p>
                            ) : versionData?.panel.latest === versionData?.panel.current ? (
                                <p css={tw`text-neutral-200`}>
                                    Your panel is <span css={tw`text-neutral-100`}>up-to-date</span>. The latest version
                                    is <Code>{versionData?.panel.latest}</Code> and you are running version{' '}
                                    <Code>{versionData?.panel.current}</Code>.
                                </p>
                            ) : (
                                <p css={tw`text-neutral-200`}>
                                    Your panel is <span css={tw`text-neutral-100`}>not up-to-date</span>. The latest
                                    version is <Code>{versionData?.panel.latest}</Code> and you are running version{' '}
                                    <Code>{versionData?.panel.current}</Code>.
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AdminContentBlock>
    );
};
