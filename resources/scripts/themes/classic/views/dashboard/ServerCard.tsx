import { cn } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

/** The card's pieces are views of their own, so a theme replaces one and keeps the others. */
export default function ServerCard({ model, server, className }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className={cn('grid grid-cols-12 gap-4', className)}>
            <View name='dashboard.serverCard.identity' model={model} server={server} />
            <View name='dashboard.serverCard.address' model={model} server={server} />
            <View name='dashboard.serverCard.metrics' model={model} server={server} />
        </div>
    );
}
