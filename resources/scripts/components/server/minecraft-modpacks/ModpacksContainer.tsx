import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import Pagination from '@/components/elements/Pagination';
import Select from '@/components/elements/Select';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import React, { useEffect, useState } from 'react';
import { useLocation } from 'react-router';
import tw from 'twin.macro';
import ModpackRow from './ModpackRow';
import getMinecraftModpacks, { ModpackProvider } from '@/api/swr/getMinecraftModpacks';

export default () => {
    const { search } = useLocation();
    const defaultPage = Number(new URLSearchParams(search).get('page') || 1);

    const [provider, setProvider] = useState<ModpackProvider>('modrinth');
    const [searchQuery, setSearchQuery] = useState('');
    const [pageSize, setPageSize] = useState(50);
    const [page, setPage] = useState(!isNaN(defaultPage) && defaultPage > 0 ? defaultPage : 1);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const shortUuid = ServerContext.useStoreState((state) => state.server.data!.id);
    const { data: modpacks, error, isValidating } = getMinecraftModpacks(provider, searchQuery, pageSize, page);

    useEffect(() => {
        if (!modpacks) return;
        if (modpacks.pagination.currentPage > 1 && !modpacks.items.length) {
            setPage(1);
        }
    }, [modpacks?.pagination.currentPage]);

    useEffect(() => {
        if (!error) {
            clearFlashes('modpacks');

            return;
        }

        clearAndAddHttpError({ error, key: 'modpacks' });
    }, [error]);

    useEffect(() => {
        // Don't use react-router to handle changing this part of the URL, otherwise it
        // triggers a needless re-render. We just want to track this in the URL incase the
        // user refreshes the page.
        window.history.replaceState(
            null,
            document.title,
            `/server/${shortUuid}/modpacks${page <= 1 ? '' : `?page=${page}`}`
        );
    }, [page]);

    let section;

    if (!modpacks || (error && isValidating)) {
        section = <Spinner size={'large'} centered />;
    } else if (modpacks) {
        section = (
            <Pagination data={modpacks} onPageSelect={setPage}>
                {({ items }) =>
                    items.length > 0 ? (
                        <div className='grid lg:grid-cols-3 gap-2'>
                            {items.map((modpack) => (
                                <ModpackRow key={modpack.id} provider={provider} modpack={modpack} />
                            ))}
                        </div>
                    ) : (
                        <p css={tw`text-center text-sm text-neutral-300`}>
                            There are no modpacks to display for this query.
                        </p>
                    )
                }
            </Pagination>
        );
    }

    return (
        <ServerContentBlock title={'Modpacks'} showFlashKey='modpacks'>
            {modpacks?.installed_modpack && (
                <div className='mb-6'>
                    <Label>Most Recently Installed Modpack</Label>
                    <ModpackRow
                        provider={modpacks.installed_modpack.provider as ModpackProvider}
                        modpack={modpacks.installed_modpack}
                    />
                </div>
            )}
            <div css={tw`flex flex-wrap gap-4`}>
                <div>
                    <Label htmlFor={'provider'}>Provider</Label>
                    <Select
                        name='provider'
                        value={provider}
                        onChange={(event) => {
                            setProvider(event.target.value as ModpackProvider);
                        }}
                    >
                        <option value='atlauncher'>ATLauncher</option>
                        <option value='curseforge'>CurseForge</option>
                        <option value='feedthebeast'>Feed The Beast</option>
                        <option value='modrinth'>Modrinth</option>
                        <option value='technic'>Technic</option>
                        <option value='voidswrath'>Voids Wrath</option>
                    </Select>
                </div>
                <div>
                    <Label htmlFor={'page_size'}>Page size</Label>
                    <Select
                        name='page_size'
                        disabled={provider === 'voidswrath'}
                        value={pageSize}
                        onChange={(event) => {
                            setPageSize(Number(event.target.value));
                        }}
                    >
                        <option value='10'>10</option>
                        <option value='25'>25</option>
                        <option value='50'>50</option>
                    </Select>
                </div>
                <div css={tw`w-full md:w-auto md:flex-1`}>
                    <Label htmlFor={'search_query'}>Search query</Label>
                    <Input
                        type='search'
                        id='search_query'
                        className='h-[2.875rem]'
                        disabled={provider === 'voidswrath'}
                        value={searchQuery}
                        onChange={(event) => {
                            setSearchQuery(event.target.value);
                        }}
                    />
                </div>
            </div>
            <div css={tw`mt-3`}>{section}</div>
        </ServerContentBlock>
    );
};
