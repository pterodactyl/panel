import type * as React from 'react';
import { X, type LucideIcon } from 'lucide-react';
import { NewButton } from '@/components/elements/NewButton';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { useTranslation } from '@/sdk/localization';

interface Props {
    icon: LucideIcon;
    filter?: string;
    onClearFilter: () => void;
    empty: string;
    noMatch: string;
    noMatchDescription: React.ReactNode;
    description: React.ReactNode;
    action?: React.ReactNode;
}

export const filterHighlight = { filter: <span className='font-medium text-foreground' /> };

/** Empty state for a searchable admin list: "No servers yet", or "No matching servers" with a way back. */
export default function AdminListEmpty({
    icon: ResourceIcon,
    filter,
    empty,
    noMatch,
    noMatchDescription,
    onClearFilter,
    description,
    action,
}: Props) {
    const { t } = useTranslation();
    const content = filter ? (
        <NewButton isSecondary icon={X} onClick={onClearFilter}>
            {t('Clear search')}
        </NewButton>
    ) : (
        action
    );

    return (
        <Empty size='compact'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <ResourceIcon />
                </EmptyMedia>
                <EmptyTitle>{filter ? noMatch : empty}</EmptyTitle>
                <EmptyDescription>{filter ? noMatchDescription : description}</EmptyDescription>
            </EmptyHeader>
            {content && <EmptyContent>{content}</EmptyContent>}
        </Empty>
    );
}
