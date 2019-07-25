import http from '@/api/http';

interface Data {
    current: string;
    password: string;
    confirmPassword: string;
}

export default ({ current, password, confirmPassword }: Data): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/password', {
            // eslint-disable-next-line @typescript-eslint/camelcase
            current_password: current,
            password: password,
            // eslint-disable-next-line @typescript-eslint/camelcase
            password_confirmation: confirmPassword,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
