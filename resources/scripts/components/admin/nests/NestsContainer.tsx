import React, { useEffect, useState } from 'react';
import getNests from '@/api/admin/nests/getNests';
import { httpErrorToHuman } from '@/api/http';
import NewNestButton from '@/components/admin/nests/NewNestButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, {
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/admin/AdminTable';

const RowCheckbox = ({ id }: { id: number}) => {
    const isChecked = AdminContext.useStoreState(state => state.nests.selectedNests.indexOf(id) >= 0);
    const appendSelectedNest = AdminContext.useStoreActions(actions => actions.nests.appendSelectedNest);
    const removeSelectedNest = AdminContext.useStoreActions(actions => actions.nests.removeSelectedNest);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedNest(id);
                } else {
                    removeSelectedNest(id);
                }
            }}
        />
    );
};

export default () => {
    const match = useRouteMatch();

    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const nests = useDeepMemoize(AdminContext.useStoreState(state => state.nests.data));
    const setNests = AdminContext.useStoreActions(state => state.nests.setNests);

    const setSelectedNests = AdminContext.useStoreActions(actions => actions.nests.setSelectedNests);
    const selectedNestsLength = AdminContext.useStoreState(state => state.nests.selectedNests.length);

    useEffect(() => {
        setLoading(!nests.length);
        clearFlashes('nests');

        getNests()
            .then(nests => setNests(nests))
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'nests' });
            })
            .then(() => setLoading(false));
    }, []);

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedNests(e.currentTarget.checked ? (nests.map(nest => nest.id) || []) : []);
    };

    return (
        <AdminContentBlock>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Nests</h2>
                    <p css={tw`text-base text-neutral-400`}>All nests currently available on this system.</p>
                </div>

                <NewNestButton/>
            </div>

            <FlashMessageRender byKey={'nests'} css={tw`mb-4`}/>

            <AdminTable
                loading={loading}
                hasItems={nests.length > 0}
                checked={selectedNestsLength === (nests.length === 0 ? -1 : nests.length)}
                onSelectAllClick={onSelectAllClick}
            >
                <TableHead>
                    <TableHeader name={'ID'}/>
                    <TableHeader name={'Name'}/>
                    <TableHeader name={'Description'}/>

                    {/* <th css={tw`px-6 py-2`}/> */}
                </TableHead>

                <TableBody>
                    {
                        nests.map(nest => (
                            <TableRow key={nest.id}>
                                <td css={tw`pl-6`}>
                                    <RowCheckbox id={nest.id}/>
                                </td>

                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{nest.id}</td>
                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                    <NavLink to={`${match.url}/${nest.id}`}>
                                        {nest.name}
                                    </NavLink>
                                </td>
                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{nest.description}</td>
                            </TableRow>
                        ))
                    }
                </TableBody>
            </AdminTable>
        </AdminContentBlock>
    );
};
