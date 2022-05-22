import http from '@/api/http';

interface Data {
    username: string;
    password: string;
}

export default ({ username, password }: Data): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/username', {
            username: username,
            password: password,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
