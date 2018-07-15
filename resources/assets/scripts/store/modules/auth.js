import User from './../../models/user';

const route = require('./../../../../../vendor/tightenco/ziggy/src/js/route').default;

export const authModule = {
    namespaced: true,
    state: {
        user: typeof window.PterodactylUser === 'object' ? new User(window.PterodactylUser) : null,
    },
    getters: {
        /**
         * Return the currently authenticated user.
         *
         * @param state
         * @returns {User|null}
         */
        getUser: function (state) {
            return state.user;
        },
    },
    setters: {},
    actions: {
        /**
         * Log a user into the Panel.
         *
         * @param commit
         * @param {String} user
         * @param {String} password
         * @returns {Promise<any>}
         */
        login: ({commit}, {user, password}) => {
            return new Promise((resolve, reject) => {
                window.axios.post(route('auth.login'), {user, password})
                    .then(response => {
                        commit('logout');

                        // If there is a 302 redirect or some other odd behavior (basically, response that isnt
                        // in JSON format) throw an error and don't try to continue with the login.
                        if (!(response.data instanceof Object)) {
                            return reject(new Error('An error was encountered while processing this request.'));
                        }

                        if (response.data.complete) {
                            commit('login', response.data.user);
                            return resolve({
                                complete: true,
                                intended: response.data.intended,
                            });
                        }

                        return resolve({
                            complete: false,
                            token: response.data.login_token,
                        });
                    })
                    .catch(reject);
            });
        },

        /**
         * Update a user's email address on the Panel and store the updated result in Vuex.
         *
         * @param commit
         * @param {String} email
         * @param {String} password
         * @return {Promise<any>}
         */
        updateEmail: function ({commit}, {email, password}) {
            return new Promise((resolve, reject) => {
                window.axios.put(route('api.client.account.update-email'), {email, password})
                    .then(response => {
                        // If there is a 302 redirect or some other odd behavior (basically, response that isnt
                        // in JSON format) throw an error and don't try to continue with the login.
                        if (!(response.data instanceof Object) && response.status !== 201) {
                            return reject(new Error('An error was encountered while processing this request.'));
                        }

                        commit('setEmail', email);
                        return resolve();
                    })
                    .catch(reject);
            });
        },
    },
    mutations: {
        setEmail: function (state, email) {
            state.user.email = email;
        },
        login: function (state, data) {
            state.user = new User(data);
        },
        logout: function (state) {
            state.user = null;
        },
    },
};
