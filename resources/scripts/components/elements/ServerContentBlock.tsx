import type { PageContentBlockProps } from '@/components/elements/PageContentBlock';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { useCurrentServerName } from '@/api/server/queries';

interface Props extends PageContentBlockProps {
    /** Shown after the server name in the document title; omit to leave the title alone. */
    title?: string;
}

const ServerContentBlock = ({ title, children, ...props }: Props) => {
    const name = useCurrentServerName()!;

    return (
        <PageContentBlock title={title ? `${name} | ${title}` : undefined} {...props}>
            {children}
        </PageContentBlock>
    );
};

export default ServerContentBlock;
