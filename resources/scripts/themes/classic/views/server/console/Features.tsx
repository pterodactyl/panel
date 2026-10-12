import { View, type ViewProps } from '@/sdk/theme';

export default function Features({ model }: ViewProps<'server.features'>) {
    return model.views.map((name) => <View key={name} name={name} />);
}
