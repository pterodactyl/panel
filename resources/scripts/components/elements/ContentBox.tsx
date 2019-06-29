import * as React from 'react';
import classNames from 'classnames';
import FlashMessageRender from '@/components/FlashMessageRender';

type Props = Readonly<React.DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> & {
    title?: string;
    borderColor?: string;
    showFlashes?: string | boolean;
}>;

export default ({ title, borderColor, showFlashes, children, ...props }: Props) => (
    <div {...props}>
        {title && <h2 className={'text-neutral-300 mb-4 px-4'}>{title}</h2>}
        {showFlashes && <FlashMessageRender byKey={typeof showFlashes === 'string' ? showFlashes : undefined}/>}
        <div className={classNames('bg-neutral-700 p-4 rounded shadow-lg relative', borderColor, {
            'border-t-4': !!borderColor,
        })}>
            {children}
        </div>
    </div>
);
