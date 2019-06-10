import http from '@/api/http';

interface LoginResponse {
    complete: boolean;
    intended?: string;
    token?: string;
}

export default (user: string, password: string): Promise<LoginResponse> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/login', { user, password })
            .then(response => {
                if (!(response.data instanceof Object)) {
                    return reject(new Error('An error occurred while processing the login request.'));
                }

                return resolve({
                    complete: response.data.complete,
                    intended: response.data.intended || undefined,
                    token: response.data.token || undefined,
                });
            })
            .catch(reject);
    });
};
