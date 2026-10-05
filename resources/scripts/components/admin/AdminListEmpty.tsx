import type * as React from 'react';
import { X, type LucideIcon } from 'lucide-react';
import { NewButton } from '@/components/elements/NewButton';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

interface Props {
    icon: LucideIcon;
    /** Plural, lower-case resource name, e.g. "servers". */
    noun: string;
    filter?: string;
    onClearFilter: () => void;
    description: React.ReactNode;
    action?: React.ReactNode;
}

/** Empty state for a searchable admin list: "No servers yet", or "No matching servers" with a way back. */
export default function AdminListEmpty({
    icon: ResourceIcon,
    noun,
    filter,
    onClearFilter,
    description,
    action,
}: Props) {
    const content = filter ? (
        <NewButton isSecondary icon={X} onClick={onClearFilter}>
            Clear search
        </NewButton>
    ) : (
        action
    );

    return (
        <Empty className={emptyCompactClass}>
            <EmptyHeader>
                <EmptyMedia variant={'icon'}>
                    <ResourceIcon />
                </EmptyMedia>
                <EmptyTitle>{filter ? `No matching ${noun}` : `No ${noun} yet`}</EmptyTitle>
                <EmptyDescription>
                    {filter ? (
                        <>
                            No {noun} match <span className={'font-medium text-foreground'}>{filter}</span>.
                        </>
                    ) : (
                        description
                    )}
                </EmptyDescription>
            </EmptyHeader>
            {content && <EmptyContent>{content}</EmptyContent>}
        </Empty>
    );
}
