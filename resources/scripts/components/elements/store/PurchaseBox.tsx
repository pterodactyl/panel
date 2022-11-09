import { Button } from '@/components/elements/button';
import React, { Dispatch, SetStateAction } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

interface BoxProps {
    type: string;
    icon: React.ReactElement;
    amount: number;
    suffix?: string;
    description: string;
    cost: number;

    setOpen: Dispatch<SetStateAction<boolean>>;
    setResource: Dispatch<SetStateAction<string>>;
}

export default (props: BoxProps) => (
    <TitledGreyBox title={'Purchase ' + props.type}>
        <div className={'flex flex-row justify-center items-center my-2'}>
            {props.icon}
            <Button.Success
                className={'ml-4'}
                onClick={() => {
                    props.setOpen(true);
                    props.setResource(props.type.toLowerCase());
                }}
            >
                +{props.amount}
                {props.suffix} {props.type}
            </Button.Success>
        </div>
        <p className={'mt-2 text-gray-500 text-xs flex justify-center'}>{props.description}</p>
        <p className={'mt-1 text-gray-500 text-xs flex justify-center'}>
            Cost per {props.amount}
            {props.suffix} {props.type}: {props.cost} credits
        </p>
    </TitledGreyBox>
);
