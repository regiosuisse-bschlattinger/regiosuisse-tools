import api from '../../api';

// initial state
const state = () => ({
    all: [],
    filtered: [],
    form: {},
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
        commit('loaders/showLoader', 'forms', { root: true });
        return api.forms.getAll().then((response) => {
            commit('loaders/hideLoader', 'forms', { root: true });
            commit('setAll', response.data);
            return response.data;
        });
    },

    loadFiltered ({ commit }, params) {
        commit('loaders/showLoader', 'forms', { root: true });
        return api.forms.getFiltered(params).then((response) => {
            commit('loaders/hideLoader', 'forms', { root: true });
            commit('setFiltered', response.data);
            return response.data;
        });
    },

    load ({ commit }, id) {
        commit('loaders/showLoader', 'forms/'+id, { root: true });
        return api.forms.get(id).then((response) => {
            commit('loaders/hideLoader', 'forms/'+id, { root: true });
            commit('set', response.data);
            return response.data;
        });
    },

    create ({ commit }, payload) {
        commit('loaders/showLoader', 'forms/create', { root: true });
        return api.forms.create(payload).then((response) => {
            commit('loaders/hideLoader', 'forms/create', { root: true });
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

    update ({ commit }, payload) {
        commit('loaders/showLoader', 'forms/'+payload.id, { root: true });
        return api.forms.update(payload.id, payload).then((response) => {
            commit('loaders/hideLoader', 'forms/'+payload.id, { root: true });
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

    delete ({ commit }, id) {
        commit('loaders/showLoader', 'forms/'+id, { root: true });
        return api.forms.delete(id).then((response) => {
            commit('loaders/hideLoader', 'forms/'+id, { root: true });
            commit('remove', id);
        });
    },

};

// mutations
const mutations = {

    setAll (state, forms) {
        state.all = forms;
    },

    setFiltered (state, filtered) {
        state.filtered = filtered;
    },

    set (state, form) {
        if(form) {
            form = {
                ...form,
                translations: typeof form.translations === 'object' && form.translations !== null && !Array.isArray(form.translations) ? form.translations : {},
            };
        }
        state.form = form;
    },

    insert (state, form) {
        state.all = [...state.all, form];
    },

    update (state, form) {
        let existingForm = state.all.find(p => p.id === form.id);
        if(existingForm) {
            state.all[state.all.indexOf(existingForm)] = form;
        }
        existingForm = state.filtered.find(p => p.id === form.id);
        if(existingForm) {
            state.filtered[state.filtered.indexOf(existingForm)] = form;
        }
    },

    remove (state, id) {
        state.all = state.all.filter((form) => {
            return parseInt(form.id) !== parseInt(id);
        });
        state.filtered = state.filtered.filter((form) => {
            return parseInt(form.id) !== parseInt(id);
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