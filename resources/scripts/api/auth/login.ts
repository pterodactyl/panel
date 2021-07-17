import http from '@/api/http';

export interface LoginResponse {
    methods?: string[];
    complete: boolean;
    intended?: string;
    confirmationToken?: string;
    publicKey?: any;
}

export interface LoginData {
    username: string;
    password: string;
    recaptchaData?: string | null;
}

export default ({ username, password, recaptchaData }: LoginData): Promise<LoginResponse> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/login', {
            user: username,
            password,
            'g-recaptcha-response': recaptchaData,
        })
            .then(({ data }) => {
                if (!(data instanceof Object)) {
                    return reject(new Error('An error occurred while processing the login request.'));
                }

                return resolve({
                    methods: data.methods,
                    complete: data.complete,
                    intended: data.intended || undefined,
                    confirmationToken: data.confirmation_token || undefined,
                    // eslint-disable-next-line camelcase
                    publicKey: data.webauthn?.public_key || undefined,
                });
            })
            .catch(reject);
    });
};
