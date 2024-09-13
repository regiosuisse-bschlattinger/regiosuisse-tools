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

    translate ({ commit }, payload) {
        commit('loaders/showLoader', 'formEntries/'+payload.id, { root: true });
        return api.formEntries.translate(payload.id, payload).then((response) => {
            commit('loaders/hideLoader', 'formEntries/'+payload.id, { root: true });
            if(payload.addToInbox) {
                if(payload.inboxId) {
                    commit('inbox/update', response.data, { root: true });
                } else {
                    commit('inbox/insert', response.data, { root: true });
                }
            } else {
                commit('update', response.data);
                commit('set', response.data);
            }
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

    update (state, formEntry) {
        let existingFormEntry = state.all.find(p => p.id === formEntry.id);
        if(existingFormEntry) {
            state.all[state.all.indexOf(existingFormEntry)] = formEntry;
        }
        existingFormEntry = state.filtered.find(p => p.id === formEntry.id);
        if(existingFormEntry) {
            state.filtered[state.filtered.indexOf(existingFormEntry)] = formEntry;
        }
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