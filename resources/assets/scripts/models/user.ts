export type UserData = {
    root_admin: boolean,
    username: string,
    email: string,
    first_name: string,
    last_name: string,
    language: string,
};

/**
 * A user model that represents an user in Pterodactyl.
 */
export default class User {
    /**
     * Determines wether or not the user is an admin.
     */
    admin: boolean;

    /**
     * The username for the currently authenticated user.
     */
    username: string;

    /**
     * The currently authenticated users email address.
     */
    email: string;

    /**
     * The full name of the logged in user.
     */
    name: string;
    first_name: string;
    last_name: string;

    /**
     * The language the user has selected to use.
     */
    language: string;

    /**
     * Create a new user model.
     */
    constructor(data: UserData) {
        this.admin = data.root_admin;
        this.username = data.username;
        this.email = data.email;
        this.name = `${data.first_name} ${data.last_name}`;
        this.first_name = data.first_name;
        this.last_name = data.last_name;
        this.language = data.language;
    }
}
