import * as React from 'react';
import classNames from 'classnames';

type Props = React.InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    description?: string;
    value?: string;
};

export default React.forwardRef<HTMLInputElement, Props>(({ className, description, onChange, label, value, ...props }, ref) => {
    const [ stateValue, setStateValue ] = React.useState(value);

    if (value !== stateValue) {
        setStateValue(value);
    }

    const classes = classNames('input open-label', {
        'has-content': stateValue && stateValue.length > 0,
    });

    return (
        <div className={'input-open'}>
            <input
                ref={ref}
                className={classes}
                onChange={e => {
                    setStateValue(e.target.value);
                    if (onChange) {
                        onChange(e);
                    }
                }}
                value={typeof value !== 'undefined' ? (stateValue || '') : undefined}
                {...props}
            />
            <label htmlFor={props.id}>{label}</label>
            {description &&
            <p className={'mt-2 text-xs text-neutral-500'}>
                {description}
            </p>
            }
        </div>
    );
});
