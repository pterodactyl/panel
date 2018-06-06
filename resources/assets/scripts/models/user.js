import axios from './../helpers/axios';

export default class User {
    /**
     * Get a new user model by hitting the Panel API using the authentication token
     * provided. If no user can be retrieved null will be returned.
     *
     * @return {User|null}
     */
    static fromCookie() {
        axios.get('/api/client/account')
            .then(response => {
                return new User(response.data.attributes);
            })
            .catch(err => {
                console.error(err);
                return null;
            });
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
