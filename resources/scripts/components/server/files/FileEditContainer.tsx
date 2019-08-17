import React from 'react';
import useRouter from 'use-react-router';
import queryString from 'query-string';

export default () => {
    const { location: { search } } = useRouter();
    const values = queryString.parse(search);

    return (
        <div className={'my-10'}>
            <textarea className={'rounded bg-black h-32 w-full text-neutral-100'}>

            </textarea>
        </div>
    );
};
