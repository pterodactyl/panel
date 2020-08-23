import useServer from '@/plugins/useServer';
import PageContentBlock, { PageContentBlockProps } from '@/components/elements/PageContentBlock';
import React from 'react';

interface Props extends PageContentBlockProps {
    title: string;
}

const ServerContentBlock: React.FC<Props> = ({ title, children, ...props }) => {
    const { name } = useServer();

    return (
        <PageContentBlock title={`${name} | ${title}`} {...props}>
            {children}
        </PageContentBlock>
    );
};

export default ServerContentBlock;
