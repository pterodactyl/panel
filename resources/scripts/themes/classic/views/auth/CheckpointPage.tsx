import { View, type ViewProps } from '@/sdk/theme';

export default function CheckpointPage({ className }: ViewProps<'page.auth.checkpoint'>) {
    return <View name='auth.checkpointForm' className={className} />;
}
