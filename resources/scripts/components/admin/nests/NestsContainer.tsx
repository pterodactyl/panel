import AdminCheckbox from '@/components/admin/AdminCheckbox';
import React, { useEffect, useState } from 'react';
import getNests from '@/api/admin/nests/getNests';
import { httpErrorToHuman } from '@/api/http';
import NewNestButton from '@/components/admin/nests/NewNestButton';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import useFlash from '@/plugins/useFlash';
import { AdminContext } from '@/state/admin';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';

export default () => {
    const match = useRouteMatch();

    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const nests = useDeepMemoize(AdminContext.useStoreState(state => state.nests.data));
    const setNests = AdminContext.useStoreActions(state => state.nests.setNests);

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

            <div css={tw`w-full flex flex-col`}>
                <div css={tw`w-full flex flex-col bg-neutral-700 rounded-lg shadow-md`}>
                    { loading ?
                        <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                            <Spinner size={'base'}/>
                        </div>
                        :
                        nests.length < 1 ?
                            <div css={tw`w-full flex flex-col items-center justify-center pb-6 py-2 sm:py-8 md:py-10 px-8`}>
                                <div css={tw`h-64 flex`}>
                                    <img src={'/assets/svgs/not_found.svg'} alt={'No Items'} css={tw`h-full select-none`}/>
                                </div>

                                <p css={tw`text-lg text-neutral-300 text-center font-normal sm:mt-8`}>No items could be found, it&apos;s almost like they are hiding.</p>
                            </div>
                            :
                            <>
                                <div css={tw`flex flex-row items-center h-12 px-6`}>
                                    <div css={tw`flex flex-row items-center`}>
                                        <AdminCheckbox name={'selectAll'}/>

                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" css={tw`w-4 h-4 ml-1 text-neutral-200`}>
                                            <path clipRule="evenodd" fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                        </svg>
                                    </div>

                                    <div css={tw`flex flex-row items-center px-2 py-1 ml-auto rounded cursor-pointer bg-neutral-600`}>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" css={tw`w-6 h-6 text-neutral-300`}>
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                        </svg>

                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" css={tw`w-4 h-4 ml-1 text-neutral-200`}>
                                            <path clipRule="evenodd" fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                        </svg>
                                    </div>
                                </div>

                                <div css={tw`overflow-x-auto`}>
                                    <table css={tw`w-full table-auto`}>
                                        <thead css={tw`bg-neutral-900 border-t border-b border-neutral-500`}>
                                            <tr>
                                                <th css={tw`px-6 py-2`}/>

                                                <th css={tw`px-6 py-2`}>
                                                    <span css={tw`flex flex-row items-center cursor-pointer`}>
                                                        <span css={tw`text-xs font-medium tracking-wider uppercase text-neutral-300 whitespace-nowrap`}>ID</span>

                                                        <div css={tw`ml-1`}>
                                                            <svg fill="none" viewBox="0 0 20 20" css={tw`w-4 h-4 text-neutral-400`}>
                                                                <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M13 7L10 4L7 7"/>
                                                                <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M7 13L10 16L13 13"/>
                                                            </svg>
                                                        </div>
                                                    </span>
                                                </th>

                                                <th css={tw`px-6 py-2`}>
                                                    <span css={tw`flex flex-row items-center cursor-pointer`}>
                                                        <span css={tw`text-xs font-medium tracking-wider uppercase text-neutral-300 whitespace-nowrap`}>Name</span>

                                                        <div css={tw`ml-1`}>
                                                            <svg fill="none" viewBox="0 0 20 20" css={tw`w-4 h-4 text-neutral-400`}>
                                                                <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M13 7L10 4L7 7"/>
                                                                <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M7 13L10 16L13 13"/>
                                                            </svg>
                                                        </div>
                                                    </span>
                                                </th>

                                                <th css={tw`px-6 py-2`}>
                                                    <span css={tw`flex flex-row items-center cursor-pointer`}>
                                                        <span css={tw`text-xs font-medium tracking-wider uppercase text-neutral-300 whitespace-nowrap`}>Description</span>

                                                        <div css={tw`ml-1`}>
                                                            <svg fill="none" viewBox="0 0 20 20" css={tw`w-4 h-4 text-neutral-400`}>
                                                                <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M13 7L10 4L7 7"/>
                                                                <path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M7 13L10 16L13 13"/>
                                                            </svg>
                                                        </div>
                                                    </span>
                                                </th>

                                                {/* <th css={tw`px-6 py-2`}/> */}
                                            </tr>
                                        </thead>

                                        <tbody>
                                            {
                                                nests.map(nest => (
                                                    <tr key={nest.id} css={tw`h-12 hover:bg-neutral-600`}>
                                                        <td css={tw`pl-6`}>
                                                            <AdminCheckbox name={nest.id.toString()}/>
                                                        </td>

                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap pl-8`}>{nest.id}</td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                            <NavLink to={`${match.url}/${nest.id}`}>
                                                                {nest.name}
                                                            </NavLink>
                                                        </td>
                                                        <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap pr-8`}>{nest.description}</td>
                                                    </tr>
                                                ))
                                            }
                                        </tbody>
                                    </table>
                                </div>

                                <div css={tw`flex flex-row items-center w-full px-6 py-3 border-t border-neutral-500`}>
                                    <p css={tw`text-sm leading-5 text-neutral-400`}>
                                        Showing <span css={tw`text-neutral-300`}>1</span> to <span css={tw`text-neutral-300`}>10</span> of <span css={tw`text-neutral-300`}>97</span> results
                                    </p>

                                    <div css={tw`flex flex-row ml-auto`}>
                                        <nav css={tw`relative z-0 inline-flex shadow-sm`}>
                                            <a href="javascript:void(0)" css={tw`relative inline-flex items-center px-1 py-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border rounded-l-md border-neutral-500 bg-neutral-600 text-neutral-400 hover:text-neutral-200 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-500`} aria-label="Previous">
                                                <svg css={tw`w-5 h-5`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path clipRule="evenodd" fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/>
                                                </svg>
                                            </a>

                                            <a href="javascript:void(0)" css={tw`relative inline-flex items-center px-3 py-1 -ml-px text-sm font-medium leading-5 transition duration-150 ease-in-out border border-neutral-500 bg-neutral-500 text-neutral-50 focus:z-10 focus:outline-none focus:border-primary-300`}>
                                                1
                                            </a>

                                            <a href="javascript:void(0)" css={tw`relative inline-flex items-center px-3 py-1 -ml-px text-sm font-medium leading-5 transition duration-150 ease-in-out border border-neutral-500 bg-neutral-600 text-neutral-200 hover:text-neutral-300 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-700`}>
                                                2
                                            </a>

                                            <a href="javascript:void(0)" css={tw`relative items-center hidden px-3 py-1 -ml-px text-sm font-medium leading-5 transition duration-150 ease-in-out border md:inline-flex border-neutral-500 bg-neutral-600 text-neutral-200 hover:text-neutral-300 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-700`}>
                                                3
                                            </a>

                                            <span css={tw`relative inline-flex items-center px-3 py-1 -ml-px text-sm font-medium leading-5 border border-neutral-500 bg-neutral-600 text-neutral-200 cursor-default`}>
                                                ...
                                            </span>

                                            <a href="javascript:void(0)" css={tw`relative items-center hidden px-3 py-1 -ml-px text-sm font-medium leading-5 transition duration-150 ease-in-out border md:inline-flex border-neutral-500 bg-neutral-600 text-neutral-200 hover:text-neutral-300 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-700`}>
                                                7
                                            </a>

                                            <a href="javascript:void(0)" css={tw`relative inline-flex items-center px-3 py-1 -ml-px text-sm font-medium leading-5 transition duration-150 ease-in-out border border-neutral-500 bg-neutral-600 text-neutral-200 hover:text-neutral-300 focus:z-10 focus:outline-none focus:border-primary-300  active:bg-neutral-100 active:text-neutral-700`}>
                                                8
                                            </a>

                                            <a href="javascript:void(0)" css={tw`relative inline-flex items-center px-3 py-1 -ml-px text-sm font-medium leading-5 transition duration-150 ease-in-out border border-neutral-500 bg-neutral-600 text-neutral-200 hover:text-neutral-300 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-700`}>
                                                9
                                            </a>

                                            <a href="javascript:void(0)" css={tw`relative inline-flex items-center px-1 py-1 text-sm font-medium leading-5 transition duration-150 ease-in-out border rounded-r-md border-neutral-500 bg-neutral-600 text-neutral-400 hover:text-neutral-200 focus:z-10 focus:outline-none focus:border-primary-300 active:bg-neutral-100 active:text-neutral-500`} aria-label="Previous">
                                                <svg css={tw`w-5 h-5`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path clipRule="evenodd" fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/>
                                                </svg>
                                            </a>
                                        </nav>
                                    </div>
                                </div>
                            </>
                    }
                </div>
            </div>
        </AdminContentBlock>
    );
};
