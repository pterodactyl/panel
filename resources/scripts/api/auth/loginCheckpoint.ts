import http from '@/api/http';
import { LoginResponse } from '@/api/auth/login';

export default (token: string, code: string): Promise<LoginResponse> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/login/checkpoint', {
            // eslint-disable-next-line @typescript-eslint/camelcase
            confirmation_token: token,
            // eslint-disable-next-line @typescript-eslint/camelcase
            authentication_code: code,
        })
            .then(response => resolve({
                complete: response.data.data.complete,
                intended: response.data.data.intended || undefined,
            }))
            .catch(reject);
    });
};
