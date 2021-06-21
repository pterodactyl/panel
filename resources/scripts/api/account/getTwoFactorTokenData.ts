import http from "@/api/http";

export interface TwoFactorTokenData {
    image_url_data: string;
    secret: string;
}

export default (): Promise<TwoFactorTokenData> => {
    return new Promise((resolve, reject) => {
        http.get("/api/client/account/two-factor")
            .then(({ data }) => resolve(data.data))
            .catch(reject);
    });
};
