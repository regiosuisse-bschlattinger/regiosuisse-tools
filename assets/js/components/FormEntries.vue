<template>

    <div class="form-entries-component">

        <div class="form-entries-component-title">

            <h2>Formulareinträge</h2>

            <transition name="fade" mode="out-in">
                <div class="loading-indicator" v-if="isLoading('formEntries')"></div>
            </transition>

            <div class="form-entries-component-title-actions">
                <div class="button primary" :class="{ disabled : !selectedFormEntryIds.length }" @click="clickExportFormEntriesDocx()">Export: DOCX</div>
                <div class="button success" :class="{ disabled : !selectedFormEntryIds.length }" @click="clickExportFormEntriesXlsx()">Export: XLSX</div>
                <a class="button" target="_blank" :href="'/embed/iframe/forms-de/'+selectedForm.id+'.html'">Vorschau</a>
                <router-link :to="'/forms/'+selectedForm.id+'/edit'" class="button primary" :class="{ disabled: formEntries.length }">Formular bearbeiten</router-link>
            </div>

        </div>

        <div class="form-entries-component-content">

            <table class="table">
                <thead>
                <tr>
                    <th><input id="form-entries" type="checkbox" :checked="selectedFormEntryIds.length === formEntries.length" @input="clickToggleFormEntries()"></th>
                    <th>ID</th>
                    <th>Erstellt</th>
                    <th v-for="field in visibleFields">{{ field.name }}</th>
                </tr>
                </thead>
                <tbody v-if="!formEntries.length && isLoading('formEntries')">
                <tr>
                    <td colspan="11"><em>Formulareinträge werden geladen...</em></td>
                </tr>
                </tbody>
                <tbody v-else>
                <tr v-for="formEntry in formEntries"
                    class="clickable"
                    @click="clickFormEntry(formEntry)">
                    <td><input @click.stop id="form-entry" type="checkbox" :checked="selectedFormEntryIds.includes(formEntry.id)" @input="clickToggleFormEntry($event, formEntry.id)"></td>
                    <td>{{ formEntry.id }}</td>
                    <td>{{ formEntry.createdAt ? $helpers.formatDateTime(formEntry.createdAt) : '-' }}</td>
                    <td v-for="field in visibleFields">{{ formEntry.content[field.identifier] || '' }}</td>
                </tr>
                </tbody>
            </table>

        </div>

    </div>

</template>

<script>
import {mapGetters, mapState} from 'vuex';
import moment from 'moment';

export default {
    data() {
        return {
            form: {},
            selectedFormEntryIds: [],
        }
    },
    computed: {
        ...mapState({
            selectedForm: state => state.forms.form,
            formEntries: state => state.formEntries.filtered,
        }),
        ...mapGetters({
            isLoading: 'loaders/isLoading',
        }),
        visibleFields() {
            let fields = [];

            if(!this.form?.config) {
                return [];
            }

            for(let formGroup of this.form.config) {
                for(let field of formGroup.fields) {
                    if(field.visible) {
                        fields.push(field);
                    }
                }
            }

            return fields;
        },
    },
    methods: {
        reloadFormEntries() {
            if (this.$route.params.id) {
                this.$store.commit('forms/set', {});
                this.$store.dispatch('forms/load', this.$route.params.id).then(() => {
                    this.form = this.selectedForm;
                    return this.$store.dispatch('formEntries/loadFiltered', {form: this.selectedForm.id});
                });
            }
        },
        clickFormEntry(formEntry) {
            this.$router.push({
                path: '/forms/' + this.selectedForm.id + '/entries/' + formEntry.id
            });
        },
        clickToggleFormEntry(event, formEntryId) {
            if (event.target.checked) {
                if (!this.selectedFormEntryIds.includes(formEntryId)) {
                    this.selectedFormEntryIds.push(formEntryId);
                }
            } else {
                let idIndex = this.selectedFormEntryIds.indexOf(formEntryId);
                if (idIndex >= 0) {
                    this.selectedFormEntryIds.splice(idIndex, 1);
                }
            }
        },
        clickToggleFormEntries() {
            if (this.selectedFormEntryIds.length !== this.formEntries.length) {
                return this.selectedFormEntryIds = this.formEntries.map((formEntry) => {
                    return formEntry.id
                });
            }

            this.selectedFormEntryIds = [];
        },
        clickExportFormEntriesDocx() {
            let formEntryIds = [];
            for (let id of this.selectedFormEntryIds.sort()) {
                formEntryIds.push('ids[]=' + id);
            }

            window.location.href = '/api/v1/form-entries.docx?form=' + this.form.id + '&' + formEntryIds.join('&');
        },
        clickExportFormEntriesXlsx() {
            let formEntryIds = [];
            for (let id of this.selectedFormEntryIds.sort()) {
                formEntryIds.push('ids[]=' + id);
            }

            window.location.href = '/api/v1/form-entries.xlsx?form=' + this.form.id + '&' + formEntryIds.join('&');
        },
        formatDate(date, format = 'DD.MM.YYYY') {
            if (date && moment(date)) {
                return moment(date).format(format);
            }
        },
    },
    created() {
        this.reloadFormEntries();
    },
}
</script>