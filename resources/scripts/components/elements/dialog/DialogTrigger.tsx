import React, { useCallback, useLayoutEffect, useState } from 'react';

type TriggerProps = {
    onClick: () => void;
};

type DialogStateProps = {
    open: boolean;
    onClose: () => void;
};

type DialogTriggerProps = {
    children: (props: DialogStateProps) => React.ReactNode;
    trigger: (props: TriggerProps) => React.ReactNode;
};

interface DialogInstance {
    generation: number;
    open: boolean;
}

export default function DialogTrigger({ children, trigger }: DialogTriggerProps) {
    const [instance, setInstance] = useState<DialogInstance>({ generation: 0, open: false });

    // Each open mounts a new generation of the children closed, then opens it before paint.
    const show = useCallback(
        () => setInstance((current) => (current.open ? current : { generation: current.generation + 1, open: false })),
        []
    );
    const hide = useCallback(
        () => setInstance((current) => (current.open ? { ...current, open: false } : current)),
        []
    );

    useLayoutEffect(() => {
        if (instance.generation > 0) {
            setInstance((current) => (current.open ? current : { ...current, open: true }));
        }
    }, [instance.generation]);

    return (
        <>
            {instance.generation > 0 && (
                <React.Fragment key={instance.generation}>
                    {children({ open: instance.open, onClose: hide })}
                </React.Fragment>
            )}
            {trigger({ onClick: show })}
        </>
    );
}
