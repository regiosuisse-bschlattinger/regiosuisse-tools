<template>

    <div class="forms-component">

        <div class="forms-component-title">

            <h2>Formulare</h2>

            <transition name="fade" mode="out-in">
                <div class="loading-indicator" v-if="isLoading('forms')"></div>
            </transition>

            <div class="form-entries-component-title-actions">
                <router-link :to="'/forms/add'" class="button primary">Neues Formular erstellen</router-link>
            </div>

        </div>

        <div class="forms-component-filter">

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="term">Suchbegriff</label>
                        <input id="term" type="text" class="form-control" v-model="term" @change="changeForm()">
                    </div>
                </div>
            </div>

        </div>

        <div class="forms-component-content">

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bezeichnung</th>
                        <th>Einreichungen</th>
                        <th>Erstellt</th>
                        <th>Geändert</th>
                    </tr>
                </thead>
                <tbody v-if="!forms.length && isLoading('forms')">
                    <tr>
                        <td colspan="11"><em>Formulare werden geladen...</em></td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr v-for="form in forms"
                        class="clickable"
                        :class="{'warning': !form.isPublic}"
                        @click="clickForm(form)">
                        <td>{{ form.id }}</td>
                        <td>{{ translateField(form, 'name') }}</td>
                        <td>{{ form.formEntries?.length }}</td>
                        <td>{{ form.createdAt ? $helpers.formatDateTime(form.createdAt) : '-' }}</td>
                        <td>{{ form.updatedAt ? $helpers.formatDateTime(form.updatedAt) : '-' }}</td>
                    </tr>
                </tbody>
            </table>

        </div>

    </div>

</template>

<script>
    import {mapGetters, mapState} from 'vuex';
    import moment from 'moment';
    import {translateField} from '../utils/filters';

    export default {
        data () {
            return {
                term: '',
                filters: [],
            };
        },
        computed: {
            ...mapState({
                forms: state => state.forms.filtered,
            }),
            ...mapGetters({
                isLoading: 'loaders/isLoading',
            }),
        },
        methods: {
            translateField,
            changeForm () {
                this.saveFilter();
                this.reloadForms();
            },
            getFilterParams () {
                let params = {};
                params.term = this.term;

                this.filters.forEach((filter) => {
                    if(!params[filter.type]) {
                        params[filter.type] = [];
                    }
                    params[filter.type].push(filter.value);
                });

                return params;
            },
            reloadForms () {
                return this.$store.dispatch('forms/loadFiltered', this.getFilterParams());
            },
            clickForm (form) {
                this.$router.push({
                    path: '/forms/'+form.id+'/entries'
                });
            },
            formatDate(date, format = 'DD.MM.YYYY') {
                if(date && moment(date)) {
                    return moment(date).format(format);
                }
            },
            addFilter (filter) {
                if(!filter.value) {
                    return;
                }
                if(this.filters.filter(f => f.type === filter.type).find(f => f.value === filter.value)) {
                    return;
                }
                this.filters.push(filter);
                this.changeForm();
            },
            removeFilter (filter) {
                let f = this.filters.filter(f => f.type === filter.type).find(f => f.value === filter.value);
                if(f) {
                    this.filters.splice(this.filters.indexOf(f), 1);
                }
                this.changeForm();
            },
            saveFilter () {
                window.sessionStorage.setItem('pv.forms.filters', JSON.stringify(this.filters));
                window.sessionStorage.setItem('pv.forms.term', this.term);
            },
            loadFilter () {
                this.filters = JSON.parse(window.sessionStorage.getItem('pv.forms.filters') || '[]');
                this.term = window.sessionStorage.getItem('pv.forms.term') || '';
            },
        },
        created () {
            this.loadFilter();
            this.reloadForms();
        },
    }
</script>