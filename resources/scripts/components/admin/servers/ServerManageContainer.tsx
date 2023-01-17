import tw from 'twin.macro';

import { useServerFromRoute } from '@/api/admin/server';
import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';

export default () => {
    const { data: server } = useServerFromRoute();

    if (!server) return null;

    return (
        <div css={tw`grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-2 gap-y-2`}>
            <div css={tw`h-auto flex flex-col`}>
                <AdminBox title={'Reinstall Server'} css={tw`relative w-full`}>
                    <div css={tw`flex flex-row text-red-500 justify-start items-center mb-4`}>
                        <div css={tw`w-12 mr-2`}>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fillRule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </div>
                        <p css={tw`text-sm`}>Danger! This could overwrite server data.</p>
                    </div>
                    <Button size={'large'} color={'red'} css={tw`w-full`}>
                        Reinstall Server
                    </Button>
                    <p css={tw`text-xs text-neutral-400 mt-2`}>
                        This will reinstall the server with the assigned service scripts.
                    </p>
                </AdminBox>
            </div>
            <div css={tw`h-auto flex flex-col`}>
                <AdminBox title={'Install Status'} css={tw`relative w-full`}>
                    <Button size={'large'} color={'primary'} css={tw`w-full`}>
                        Set Server as Installing
                    </Button>
                    <p css={tw`text-xs text-neutral-400 mt-2`}>
                        If you need to change the install status from uninstalled to installed, or vice versa, you may
                        do so with the button below.
                    </p>
                </AdminBox>
            </div>
            <div css={tw`h-auto flex flex-col`}>
                <AdminBox title={'Suspend Server '} css={tw`relative w-full`}>
                    <Button size={'large'} color={'primary'} css={tw`w-full`}>
                        Suspend Server
                    </Button>
                    <p css={tw`text-xs text-neutral-400 mt-2`}>
                        This will suspend the server, stop any running processes, and immediately block the user from
                        being able to access their files or otherwise manage the server through the panel or API.
                    </p>
                </AdminBox>
            </div>
        </div>
    );
};
