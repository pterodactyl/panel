import { ServerContentBlock, useCurrentServerRequired } from '@pterodactyl/sdk';
import HelloWorldCard from './HelloWorldCard';

export default function HelloWorldScreen() {
    const server = useCurrentServerRequired();

    return (
        <ServerContentBlock title={'Hello World'}>
            <div style={{ display: 'grid', gap: '1rem' }}>
                <HelloWorldCard key={server.attributes.uuid} serverName={server.attributes.name} />
                <p>
                    For another example, open Users and create or edit a subuser. Permission shortcuts preselect the
                    form’s checkboxes without saving them.
                </p>
            </div>
        </ServerContentBlock>
    );
}
