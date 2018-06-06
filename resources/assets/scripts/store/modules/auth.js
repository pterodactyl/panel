import User from './../../models/user';
const route = require('./../../../../../vendor/tightenco/ziggy/src/js/route').default;

export default {
    namespaced: true,
    state: {
        user: null,
    },
    getters: {
        /**
         * Return the currently authenticated user.
         *
         * @param state
         * @returns {User|null}
         */
        currentUser: function (state) {
            return state.user;
        }
    },
    setters: {},
    actions: {
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
                            commit('login', {cookie: response.data.cookie, user: response.data.user});
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
        logout: function ({commit}) {
            return new Promise((resolve, reject) => {
                window.axios.get(route('auth.logout'))
                    .then(() => {
                        commit('logout');
                        return resolve();
                    })
                    .catch(reject);
            })
        },
    },
    mutations: {
        login: function (state, {cookie, user}) {
            state.user = new User(user);
            localStorage.setItem('token', JSON.stringify({
                name: cookie.name,
                value: cookie.value,
            }));
        },
        logout: function (state) {
            localStorage.removeItem('token');
            state.user = null;
        },
    },
};
