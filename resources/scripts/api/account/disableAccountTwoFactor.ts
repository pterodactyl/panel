import http from '@/api/http';

function disableAccountTwoFactor(password: string): Promise<void> {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/two-factor/disable', { password })
            .then(() => resolve())
            .catch(reject);
    });
}

export default disableAccountTwoFactor;
