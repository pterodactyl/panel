import { User } from './models/user';

export const storeData = {
    state: {
        user: null,
    },
    actions: {
        login: function ({ commit }) {
            commit('login');
        },
        logout: function ({ commit }) {
            commit('logout');
        },
    },
    getters: {
        getCurrentUser: function (state) {
            if (!(state.user instanceof User)) {
                state.user = User.fromJWT(localStorage.getItem('token'));
            }

            return state.user;
        },
    },
    mutations: {
        /**
         * Log in a user and store them in vuex using the local storage token.
         *
         * @param state
         */
        login: function (state) {
            state.user = User.fromJWT(localStorage.getItem('token'));
        },
        logout: function (state) {
            console.log('logout');
            state.user = null;
        }
    }
};
