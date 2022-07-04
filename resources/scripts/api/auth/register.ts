import http from '@/api/http';

export interface RegisterResponse {
    complete: boolean;
    intended?: string;
    confirmationToken?: string;
}

export interface RegisterData {
    username: string;
    email: string;
    password: string;
    recaptchaData?: string | null;
}

export default ({ username, email, password, recaptchaData }: RegisterData): Promise<RegisterResponse> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register', {
                    user: username,
                    email: email,
                    password: password,
                    'g-recaptcha-response': recaptchaData,
                })
            )
            .then((response) => {
                if (!(response.data instanceof Object)) {
                    return reject(new Error('Unable to register account. Please contact an administrator.'));
                }

                return resolve({
                    complete: response.data.data.complete,
                    intended: response.data.data.intended || undefined,
                    confirmationToken: response.data.data.confirmation_token || undefined,
                });
            })
            .catch(reject);
    });
};
