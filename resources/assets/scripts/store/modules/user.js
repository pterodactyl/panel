import {User} from "../../models/user";

export const userModule = {
    state: {
        user: null,
    },
    actions: {
        login ({ commit }) {
            commit('setUser', User.fromJWT(localStorage.getItem('token')));
        },
        logout ({ commit }) {
            commit('unsetUser');
        }
    },
    getters: {
        getCurrentUser: function (state) {
            return state.user;
        },
    },
    mutations: {
        /**
         * Log in a user and store them in vuex using the local storage token.
         *
         * @param state
         * @param user
         */
        setUser: function (state, user) {
            state.user = user;
        },
        unsetUser: function (state) {
            state.user = null;
        }
    }
};
