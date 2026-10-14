import { Button, Dialog } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import { cancelClass } from './parts';

export default function HytaleOauthPrompt({ model }: ViewProps<'server.feature.hytaleOauth'>) {
    return (
        <Dialog open={model.open} title='Authentication required' onClose={model.close}>
            <p className='text-foreground'>
                You need to authenticate with your Hytale account to download or update server files. Please log in to
                continue.
            </p>
            <div className='mt-8 sm:flex items-center justify-end'>
                <Button isSecondary onClick={model.close} className={cancelClass}>
                    Cancel
                </Button>
                <Button onClick={model.login} disabled={!model.link} className='mt-4 sm:mt-0 sm:ml-4 w-full sm:w-auto'>
                    Log in
                </Button>
            </div>
        </Dialog>
    );
}
