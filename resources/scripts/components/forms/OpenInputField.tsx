import * as React from 'react';
import classNames from 'classnames';

type Props = React.InputHTMLAttributes<HTMLInputElement> & {
    label: string;
};

export default ({ className, onChange, label, ...props }: Props) => {
    const [ value, setValue ] = React.useState('');

    const classes = classNames('input open-label', {
        'has-content': value && value.length > 0,
    });

    return (
        <div className={'input-open'}>
            <input
                className={classes}
                onChange={e => {
                    setValue(e.target.value);
                    if (onChange) {
                        onChange(e);
                    }
                }}
                {...props}
            />
            <label htmlFor={props.id}>{label}</label>
        </div>
    );
};
