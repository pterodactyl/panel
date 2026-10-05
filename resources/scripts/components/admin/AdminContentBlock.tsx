import type { PageContentBlockProps } from '@/components/elements/PageContentBlock';
import PageContentBlock from '@/components/elements/PageContentBlock';
import PageHeading from '@/components/elements/PageHeading';

interface Props extends PageContentBlockProps {
    heading: string;
    description?: string;
}

const AdminContentBlock = ({ heading, description, children, ...props }: Props) => (
    <PageContentBlock {...props}>
        <PageHeading title={heading} description={description} />
        {children}
    </PageContentBlock>
);

export default AdminContentBlock;
