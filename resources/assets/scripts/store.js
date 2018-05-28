import User from './models/user';

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
        user: function (state) {
            return state.user;
        },
    },
    mutations: {
        login: function (state) {
            state.user = new User().fromJwt(localStorage.getItem('token'));
        },
        logout: function (state) {
            state.user = null;
        }
    }
};
