import api from '../../api';

// initial state
const state = () => ({
    all: [],
    filtered: [],
    formEntry: {},
});

// getters
const getters = {

    getById: (state) => (id) => {
        return state.all.find(item => item.id === id);
    },

};

// actions
const actions = {

    loadAll ({ commit }) {
        commit('loaders/showLoader', 'formEntries', { root: true });
        return api.formEntries.getAll().then((response) => {
            commit('loaders/hideLoader', 'formEntries', { root: true });
            commit('setAll', response.data);
            return response.data;
        });
    },

    loadFiltered ({ commit }, params) {
        commit('loaders/showLoader', 'formEntries', { root: true });
        return api.formEntries.getFiltered(params).then((response) => {
            commit('loaders/hideLoader', 'formEntries', { root: true });
            commit('setFiltered', response.data);
            return response.data;
        });
    },

    load ({ commit }, id) {
        commit('loaders/showLoader', 'formEntries/'+id, { root: true });
        return api.formEntries.get(id).then((response) => {
            commit('loaders/hideLoader', 'formEntries/'+id, { root: true });
            commit('set', response.data);
            return response.data;
        });
    },

    create ({ commit }, payload) {
        commit('loaders/showLoader', 'formEntries/create', { root: true });
        return api.formEntries.create(payload).then((response) => {
            commit('loaders/hideLoader', 'formEntries/create', { root: true });
            if(payload.addToInbox) {
                if(payload.inboxId) {
                    commit('inbox/update', response.data, { root: true });
                } else {
                    commit('inbox/insert', response.data, { root: true });
                }
            } else {
                commit('insert', response.data);
                commit('set', response.data);
            }
        });
    },

    delete ({ commit }, id) {
        commit('loaders/showLoader', 'formEntries/'+id, { root: true });
        return api.formEntries.delete(id).then((response) => {
            commit('loaders/hideLoader', 'formEntries/'+id, { root: true });
            commit('remove', id);
        });
    },

};

// mutations
const mutations = {

    setAll (state, formEntries) {
        state.all = formEntries;
    },

    setFiltered (state, filtered) {
        state.filtered = filtered;
    },

    set (state, formEntry) {
        state.formEntry = formEntry;
    },

    insert (state, formEntry) {
        state.all = [...state.all, formEntry];
    },

    remove (state, id) {
        state.all = state.all.filter((formEntry) => {
            return parseInt(formEntry.id) !== parseInt(id);
        });
        state.filtered = state.filtered.filter((formEntry) => {
            return parseInt(formEntry.id) !== parseInt(id);
        });
    },

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};