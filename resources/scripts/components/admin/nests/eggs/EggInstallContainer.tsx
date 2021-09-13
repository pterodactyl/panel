import { shell } from '@codemirror/legacy-modes/mode/shell';
import React from 'react';
import tw from 'twin.macro';
import AdminBox from '@/components/admin/AdminBox';
import { Context } from '@/components/admin/nests/eggs/EggRouter';
import Button from '@/components/elements/Button';
import Editor from '@/components/elements/Editor';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

export default () => {
    const egg = Context.useStoreState(state => state.egg);

    if (egg === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Install Script'} padding={false}>
            <div css={tw`relative pb-4`}>
                <SpinnerOverlay visible={false}/>

                <Editor overrides={tw`h-96 mb-4`} initialContent={egg.scriptInstall || ''} mode={shell}/>

                <div css={tw`mx-6 mb-4`}>
                    <div css={tw`grid grid-cols-3 gap-x-8 gap-y-6`}>
                        <div>
                            <Label>Install Container</Label>
                            <Input type="text" defaultValue={egg.scriptContainer}/>
                            <p className={'input-help'}>The Docker image to use for running this installation script.</p>
                        </div>

                        <div>
                            <Label>Install Entrypoint</Label>
                            <Input type="text" defaultValue={egg.scriptEntry}/>
                            <p className={'input-help'}>The command that should be used to run this script inside of the installation container.</p>
                        </div>
                    </div>
                </div>

                <div css={tw`flex flex-row border-t border-neutral-600`}>
                    <Button type={'button'} size={'small'} css={tw`ml-auto mr-6 mt-4`}>
                        Save Changes
                    </Button>
                </div>
            </div>
        </AdminBox>
    );
};
