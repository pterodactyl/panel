import JwtDecode from 'jwt-decode';

const User = function () {
    this.id = 0;
    this.admin = false;
    this.email = '';
};

/**
 * Return a new instance of the user model using a JWT.
 *
 * @param {string} token
 * @returns {User}
 */
User.prototype.fromJwt = function (token) {
    return this.newModel(JwtDecode(token));
};

/**
 * Return an instance of this user model with the properties set on it.
 *
 * @param {object} obj
 * @returns {User}
 */
User.prototype.newModel = function (obj) {
    this.id = obj.id;
    this.admin = obj.admin;
    this.email = obj.email;

    return this;
};

export default User;
