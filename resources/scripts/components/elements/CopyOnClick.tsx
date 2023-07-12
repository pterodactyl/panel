import classNames from 'classnames';
import copy from 'copy-to-clipboard';
import type { MouseEvent, ReactNode } from 'react';
import { Children, cloneElement, isValidElement, useEffect, useState } from 'react';

import Portal from '@/components/elements/Portal';
import FadeTransition from '@/components/elements/transitions/FadeTransition';

interface CopyOnClickProps {
    text: string | number | null | undefined;
    showInNotification?: boolean;
    children: ReactNode;
}

const CopyOnClick = ({ text, showInNotification = true, children }: CopyOnClickProps) => {
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        if (!copied) return;

        const timeout = setTimeout(() => {
            setCopied(false);
        }, 2500);

        return () => {
            clearTimeout(timeout);
        };
    }, [copied]);

    if (!isValidElement(children)) {
        throw new Error('Component passed to <CopyOnClick/> must be a valid React element.');
    }

    const child = !text
        ? Children.only(children)
        : cloneElement(Children.only(children), {
              // @ts-expect-error I don't know
              className: classNames(children.props.className || '', 'cursor-pointer'),
              onClick: (e: MouseEvent<HTMLElement>) => {
                  copy(String(text));
                  setCopied(true);
                  if (typeof children.props.onClick === 'function') {
                      children.props.onClick(e);
                  }
              },
          });

    return (
        <>
            {copied && (
                <Portal>
                    <FadeTransition show duration="duration-250" key={copied ? 'visible' : 'invisible'}>
                        <div className="fixed bottom-0 right-0 z-50 m-4">
                            <div className="rounded-md bg-neutral-600/95 py-3 px-4 text-slate-200 shadow">
                                <p>
                                    {showInNotification
                                        ? `Copied "${String(text)}" to clipboard.`
                                        : 'Copied text to clipboard.'}
                                </p>
                            </div>
                        </div>
                    </FadeTransition>
                </Portal>
            )}
            {child}
        </>
    );
};

export default CopyOnClick;
