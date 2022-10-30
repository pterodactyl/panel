import {AlertCircle} from 'react-feather';
import useFlash from '@/plugins/useFlash';
import apiVerify from '@/api/account/verify';
import { useStoreState } from '@/state/hooks';
import React, { useEffect, useState } from 'react';
import { formatDistanceToNowStrict } from 'date-fns';
import { getResources } from '@/api/store/getResources';
import Translate from '@/components/elements/Translate';
import InformationBox from '@/components/elements/InformationBox';
import getLatestActivity, { Activity } from '@/api/account/getLatestActivity';
import { wrapProperties } from '@/components/elements/activity/ActivityLogEntry';
import { faCircle, faCoins, faExclamationCircle, faScroll, faUserLock } from '@fortawesome/free-solid-svg-icons';

export default () => {
    const { addFlash } = useFlash();
    const [bal, setBal] = useState(0);
    const [activity, setActivity] = useState<Activity>();
    const properties = wrapProperties(activity?.properties);
    const user = useStoreState((state) => state.user.data!);
    const store = useStoreState((state) => state.storefront.data!);

    useEffect(() => {
        getResources().then((d) => setBal(d.balance));
        getLatestActivity().then((d) => setActivity(d));
    }, []);

    const verify = () => {
        apiVerify().then((data) => {
            if (data.success) addFlash({ type: 'info', key: 'dashboard', message: 'Verification email has been resent.' });
        })
    };

    return (
        <div className={'flex flex-col'}>
            <div className={'inline-flex'}>
                {store.earn.enabled ? (
                    <InformationBox icon={faCircle} iconCss={'animate-pulse'}>
                        Earning <span className={'text-green-600'}>{store.earn.amount}</span> credits / min.
                    </InformationBox>
                ) : (
                    <InformationBox icon={faExclamationCircle}>
                        Credit earning is currently <span className={'text-red-600'}>disabled.</span>
                    </InformationBox>
                )}
                <InformationBox icon={faCoins}>
                    You have <span className={'text-green-600'}>{bal}</span> credits available.
                </InformationBox>
                <InformationBox icon={faUserLock}>
                    {user.useTotp ? (
                        <>
                            <span className={'text-green-600'}>2FA is enabled</span> on your account.
                        </>
                    ) : (
                        <>
                            <span className={'text-yellow-600'}>Enable 2FA</span> to secure your account.
                        </>
                    )}
                </InformationBox>
                <InformationBox icon={faScroll}>
                    {activity ? (
                        <>
                        <span className={'text-neutral-400'}>
                            <Translate ns={'activity'} values={properties} i18nKey={activity.event.replace(':', '.')} />
                        </span>
                            {' - '}
                            {formatDistanceToNowStrict(activity.timestamp, { addSuffix: true })}
                        </>
                    ) : (
                        'Unable to get latest activity logs.'
                    )}
                </InformationBox>
            </div>
            {!user.verified && (
                <div className={'flex justify-center items-center mt-3 mb-1'}>
                    <AlertCircle size={16} className={'mr-1'}/>
                    <span className={'mr-2'}>Your account is not verified! Email: {user.email}</span>
                    <span onClick={verify} className={'cursor-pointer text-blue-400'}>Resend</span>
                </div>
            )}
        </div>
    );
};
