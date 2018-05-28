import { Collection, Model } from 'vue-mc';
import JwtDecode from 'jwt-decode';

export class User extends Model {
    static defaults() {
        return {
            id: null,
            uuid: '',
            username: '',
            email: '',
            name_first: '',
            name_last: '',
            language: 'en',
            root_admin: false,
        }
    }

    static mutations() {
        return {
            id: Number,
            uuid: String,
            username: String,
            email: String,
            name_first: String,
            name_last: String,
            language: String,
            root_admin: Boolean,
        }
    }

    static fromJWT(token) {
        return new User(JwtDecode(token).user || {});
    }
}

export class UserCollection extends Collection {
    static model() {
        return User;
    }

    get todo() {
        return this.sum('done');
    }

    get done() {
        return this.todo === 0;
    }
}
