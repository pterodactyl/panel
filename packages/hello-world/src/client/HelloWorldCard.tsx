import { useState } from 'react';
import {
    Button,
    SocketEvent,
    TitledGreyBox,
    toast,
    useCurrentUser,
    useServerPermission,
    useServerWebsocketEvent,
} from '@pterodactyl/sdk';

export default function HelloWorldCard({ serverName }: { serverName: string }) {
    const user = useCurrentUser();
    const canEditFiles = useServerPermission('file.update');
    const [greetings, setGreetings] = useState(0);
    const [lastStatus, setLastStatus] = useState<string | null>(null);

    useServerWebsocketEvent(SocketEvent.STATUS, setLastStatus);

    const sayHello = () => {
        setGreetings((count) => count + 1);
        toast.success(`Hello, ${user.username}!`);
    };

    return (
        <TitledGreyBox title={'Hello World'}>
            <div style={{ display: 'grid', gap: '1rem' }}>
                <p>
                    Hello, {user.username}! This extension is running on {serverName}.
                </p>
                <dl style={{ display: 'grid', gap: '0.5rem' }}>
                    <div>
                        <dt>Your file access</dt>
                        <dd>{canEditFiles ? 'You can edit files.' : 'You cannot edit files.'}</dd>
                    </div>
                    <div>
                        <dt>Latest server status event</dt>
                        <dd aria-live={'polite'}>{lastStatus ?? 'Waiting for the next status event.'}</dd>
                    </div>
                    <div>
                        <dt>Greetings this visit</dt>
                        <dd aria-live={'polite'}>{greetings}</dd>
                    </div>
                </dl>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.75rem' }}>
                    <Button onClick={sayHello}>Say hello</Button>
                    <Button.Text disabled={greetings === 0} onClick={() => setGreetings(0)}>
                        Reset counter
                    </Button.Text>
                </div>
                <p>
                    Try the buttons to update local React state. Server status changes arrive through the panel’s shared
                    websocket.
                </p>
            </div>
        </TitledGreyBox>
    );
}
