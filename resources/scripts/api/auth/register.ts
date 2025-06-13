import http from '@/api/http';

export interface RegisterResponse {
    complete: boolean;
    intended?: string;
    confirmationToken?: string;
}

export interface RegisterData {
    email: string;
    username: string;
    firstname: string;
    lastname: string;
    recaptchaData?: string | null;
}

export default ({ email, username, firstname, lastname, recaptchaData }: RegisterData): Promise<RegisterResponse> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register', {
                    email,
                    username,
                    firstname,
                    lastname,
                    'g-recaptcha-response': recaptchaData,
                })
            )
            .then((response) => {
                if (!(response.data instanceof Object)) {
                    return reject(new Error('An error occurred while processing the register request.'));
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
