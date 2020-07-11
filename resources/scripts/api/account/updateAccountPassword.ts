import http from '@/api/http';

interface Data {
    current: string;
    password: string;
    confirmPassword: string;
}

export default ({ current, password, confirmPassword }: Data): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/password', {
            current_password: current,
            password: password,
            password_confirmation: confirmPassword,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
