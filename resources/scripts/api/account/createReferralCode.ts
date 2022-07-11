import http from '@/api/http';
import { ReferralCode, rawDataToReferralCode } from '@/api/account/getReferralCodes';

export default (): Promise<ReferralCode> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/referrals')
            .then(({ data }) =>
                resolve({
                    ...rawDataToReferralCode(data.attributes),
                })
            )
            .catch(reject);
    });
};
