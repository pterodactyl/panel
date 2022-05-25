import React from 'react';
import tw from 'twin.macro';
import { useStoreState } from '@/state/hooks';

const OverviewContainer = () => {
    const enabled = useStoreState(state => state.storefront.data!.enabled);

    return (
        <p css={tw`text-green-500`}>Storefront is detected as being {enabled === '1' ? 'enabled' : 'disabled'}.</p>
    );
};

export default OverviewContainer;
