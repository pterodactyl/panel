import http from '@/api/http';

interface Data {
    code: string;
    password: string;
}

export default ({ code, password }: Data): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/use-referral', {
            code: code,
            password: password,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
