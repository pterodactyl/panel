import User, {UserData} from '../../models/user';
import {ActionContext} from "vuex";
import {AuthenticationState} from "../types";

const route = require('./../../../../../vendor/tightenco/ziggy/src/js/route').default;

type LoginAction = {
    user: string,
    password: string,
}

type UpdateEmailAction = {
    email: string,
    password: string,
}

export default {
    namespaced: true,
    state: {
        // @ts-ignore
        user: typeof window.PterodactylUser === 'object' ? new User(window.PterodactylUser) : null,
    },
    getters: {
        /**
         * Return the currently authenticated user.
         */
        getUser: function (state: AuthenticationState): null | User {
            return state.user;
        },
    },
    setters: {},
    actions: {
        /**
         * Log a user into the Panel.
         */
        login: ({commit}: ActionContext<AuthenticationState, any>, {user, password}: LoginAction): Promise<{
            complete: boolean,
            intended?: string,
            token?: string,
        }> => {
            return new Promise((resolve, reject) => {
                // @ts-ignore
                window.axios.post(route('auth.login'), {user, password})
                // @ts-ignore
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
         */
        updateEmail: function ({commit}: ActionContext<AuthenticationState, any>, {email, password}: UpdateEmailAction): Promise<void> {
            return new Promise((resolve, reject) => {
                // @ts-ignore
                window.axios.put(route('api.client.account.update-email'), {email, password})
                // @ts-ignore
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
        setEmail: function (state: AuthenticationState, email: string) {
            if (state.user) {
                state.user.email = email;
            }
        },
        login: function (state: AuthenticationState, data: UserData) {
            state.user = new User(data);
        },
        logout: function (state: AuthenticationState) {
            state.user = null;
        },
    },
};
