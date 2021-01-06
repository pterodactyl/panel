import CopyOnClick from '@/components/elements/CopyOnClick';
import React, { useContext, useEffect, useState } from 'react';
import getMounts, { Context as MountsContext } from '@/api/admin/mounts/getMounts';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, { TableBody, TableHead, TableHeader, TableRow, Pagination, Loading, NoItems, ContentWrapper } from '@/components/admin/AdminTable';
import Button from '@/components/elements/Button';

const RowCheckbox = ({ id }: { id: number}) => {
    const isChecked = AdminContext.useStoreState(state => state.mounts.selectedMounts.indexOf(id) >= 0);
    const appendSelectedMount = AdminContext.useStoreActions(actions => actions.mounts.appendSelectedMount);
    const removeSelectedMount = AdminContext.useStoreActions(actions => actions.mounts.removeSelectedMount);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedMount(id);
                } else {
                    removeSelectedMount(id);
                }
            }}
        />
    );
};

const MountsContainer = () => {
    const match = useRouteMatch();

    const { page, setPage } = useContext(MountsContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: mounts, error, isValidating } = getMounts();

    useEffect(() => {
        if (!error) {
            clearFlashes('mounts');
            return;
        }

        clearAndAddHttpError({ error, key: 'mounts' });
    }, [ error ]);

    const length = mounts?.items?.length || 0;

    const setSelectedMounts = AdminContext.useStoreActions(actions => actions.mounts.setSelectedMounts);
    const selectedMountsLength = AdminContext.useStoreState(state => state.mounts.selectedMounts.length);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedMounts(e.currentTarget.checked ? (mounts?.items?.map(mount => mount.id) || []) : []);
    };

    useEffect(() => {
        setSelectedMounts([]);
    }, [ page ]);

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Mounts</h2>
                    <p css={tw`text-base text-neutral-400`}>Configure and manage additional mount points for servers.</p>
                </div>

                <Button type={'button'} size={'large'} css={tw`h-10 ml-auto px-4 py-0`}>
                    New Mount
                </Button>
            </div>

            <FlashMessageRender byKey={'mounts'} css={tw`mb-4`}/>

            <AdminTable>
                { mounts === undefined || (error && isValidating) ?
                    <Loading/>
                    :
                    length < 1 ?
                        <NoItems/>
                        :
                        <ContentWrapper
                            checked={selectedMountsLength === (length === 0 ? -1 : length)}
                            onSelectAllClick={onSelectAllClick}
                        >
                            <Pagination data={mounts} onPageSelect={setPage}>
                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <TableHead>
                                            <TableHeader name={'ID'}/>
                                            <TableHeader name={'Name'}/>
                                            <TableHeader name={'Source Path'}/>
                                            <TableHeader name={'Target Path'}/>
                                            <th css={tw`px-6 py-2`}/>
                                            <th css={tw`px-6 py-2`}/>
                                        </TableHead>

                                        <TableBody>
                                            {
                                                mounts.items.map(mount => (
                                                    <TableRow key={mount.id}>
                                                        <td css={tw`pl-6`}>
                                                            <RowCheckbox id={mount.id}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={mount.id.toString()}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{mount.id}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${mount.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                                {mount.name}
                                                            </NavLink>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={mount.source.toString()}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{mount.source}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <CopyOnClick text={mount.target.toString()}>
                                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{mount.target}</code>
                                                            </CopyOnClick>
                                                        </td>

                                                        <td css={tw`px-6 whitespace-nowrap`}>
                                                            { mount.readOnly ?
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}>
                                                                    Read Only
                                                                </span>
                                                                :
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-yellow-200 text-yellow-800`}>
                                                                    Writable
                                                                </span>
                                                            }
                                                        </td>

                                                        <td css={tw`px-6 whitespace-nowrap`}>
                                                            { mount.userMountable ?
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-green-100 text-green-800`}>
                                                                    Mountable
                                                                </span>
                                                                :
                                                                <span css={tw`px-2 inline-flex text-xs leading-5 font-medium rounded-full bg-yellow-200 text-yellow-800`}>
                                                                    Admin Only
                                                                </span>
                                                            }
                                                        </td>
                                                    </TableRow>
                                                ))
                                            }
                                        </TableBody>
                                    </table>
                                </div>
                            </Pagination>
                        </ContentWrapper>
                }
            </AdminTable>
        </AdminContentBlock>
    );
};

export default () => {
    const [ page, setPage ] = useState<number>(1);

    return (
        <MountsContext.Provider value={{ page, setPage }}>
            <MountsContainer/>
        </MountsContext.Provider>
    );
};
