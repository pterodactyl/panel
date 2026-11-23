import { Link } from '@tanstack/react-router';
import { Tags } from 'lucide-react';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

export default function NoTagsYet() {
    const { t } = useTranslation();

    return (
        <Empty size='compact'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Tags />
                </EmptyMedia>
                <EmptyTitle>{t('No tags yet')}</EmptyTitle>
                <EmptyDescription>
                    <Translate
                        i18nKey='Type a name below to create one, or manage tags on the <link>Tags page</link>.'
                        components={{ link: <Link to='/panel/tags' /> }}
                    />
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}
