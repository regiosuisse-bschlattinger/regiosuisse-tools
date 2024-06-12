import axios from 'axios';

const endpoint = process.env.HOST+'/api/v1/form-entries';

export default {

    getAll() {
        return axios.get(endpoint);
    },

    getFiltered(params) {
        return axios.get(endpoint, {
            params,
        });
    },

    get(id) {
        return axios.get(endpoint+'/'+id);
    },

    create(payload) {
        return axios.post(endpoint, payload);
    },

    delete(id) {
        return axios.delete(endpoint+'/'+id);
    },

};