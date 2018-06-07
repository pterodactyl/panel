import isString from 'lodash/isString';
import jwtDecode from 'jwt-decode';

export default class User {
    /**
     * Get a new user model from the JWT.
     *
     * @return {User | null}
     */
    static fromToken(token) {
        if (!isString(token)) {
            token = localStorage.getItem('token');
        }

        if (!isString(token) || token.length < 1) {
            return null;
        }

        const data = jwtDecode(token);
        if (data.user) {
            return new User(data.user);
        }

        return null;
    }

    /**
     * Return the JWT for the authenticated user.
     *
     * @returns {string | null}
     */
    static getToken()
    {
        return localStorage.getItem('token');
    }

    /**
     * Create a new user model.
     *
     * @param {Boolean} admin
     * @param {String} username
     * @param {String} email
     * @param {String} first_name
     * @param {String} last_name
     * @param {String} language
     */
    constructor({
        admin,
        username,
        email,
        first_name,
        last_name,
        language,
    }) {
        this.admin = admin;
        this.username = username;
        this.email = email;
        this.name = `${first_name} ${last_name}`;
        this.first_name = first_name;
        this.last_name = last_name;
        this.language = language;
    }
}
