import { Link } from '@tanstack/react-router';
import { Tags } from 'lucide-react';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';

export default function NoTagsYet() {
    return (
        <Empty className={emptyCompactClass}>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Tags />
                </EmptyMedia>
                <EmptyTitle>No tags yet</EmptyTitle>
                <EmptyDescription>
                    Type a name below to create one, or manage tags on the <Link to='/panel/tags'>Tags page</Link>.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}
