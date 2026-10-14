import type { AdminTag } from '@/api/admin/tags/queries';
import { cn } from '@/lib/cn';
import { isString } from '@/lib/objects';

interface Props {
    /** A tag, or the name of one that saving will create. */
    tag: AdminTag | string;
    className?: string;
    showSlug?: boolean;
}

export default function TagBadge({ tag, className, showSlug = false }: Props) {
    const { color, name, slug } = isString(tag) ? { color: null, name: tag, slug: 'new' } : tag.attributes;

    return (
        <span
            className={cn(
                'inline-flex max-w-full items-center gap-2 rounded-full border border-border bg-muted px-2.5 py-1 text-xs font-medium text-foreground',
                className
            )}
        >
            <span
                aria-hidden='true'
                className='h-2 w-2 shrink-0 rounded-full bg-muted-foreground'
                style={color ? { backgroundColor: color } : undefined}
            />
            <span className='truncate'>{name}</span>
            {showSlug ? <span className='truncate font-mono text-muted-foreground'>{slug}</span> : null}
        </span>
    );
}
