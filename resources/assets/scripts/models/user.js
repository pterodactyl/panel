export default class User {
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
        root_admin,
        username,
        email,
        first_name,
        last_name,
        language,
    }) {
        this.admin = root_admin;
        this.username = username;
        this.email = email;
        this.name = `${first_name} ${last_name}`;
        this.first_name = first_name;
        this.last_name = last_name;
        this.language = language;
    }
}
