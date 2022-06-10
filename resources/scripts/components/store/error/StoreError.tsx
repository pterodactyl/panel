import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';

export default () => {
    return (
        <div css={tw`flex items-center justify-center w-full my-4`}>
            <div css={tw`flex items-center bg-neutral-900 rounded p-3 text-red-500`}>
                <Icon.AlertTriangle css={tw`h-4 w-auto mr-2`} />
                <p css={tw`text-sm text-neutral-100`}>
                    Unable to retrieve user resources.
                </p>
            </div>
        </div>
    );
};
