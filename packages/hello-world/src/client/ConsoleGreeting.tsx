import type { SdkServer, SlotComponentProps } from '@pterodactyl/sdk';
import HelloWorldCard from './HelloWorldCard';

/** Where a theme places the slot somewhere narrow, it passes `compact` and the greeting shrinks to a line. */
export default function ConsoleGreeting({ data, compact }: SlotComponentProps<SdkServer>) {
    if (compact) {
        return <p className='hw:truncate hw:text-sm'>Hello, {data.attributes.name}</p>;
    }

    return <HelloWorldCard key={data.attributes.uuid} serverName={data.attributes.name} />;
}
