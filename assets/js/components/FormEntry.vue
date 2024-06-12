<template>

    <div class="form-entry-component">

        <div class="form-entry-component-form">

            <div class="form-entry-component-form-header">

                <h3>Formulareintrag</h3>

                <div class="form-entry-component-form-header-actions">
                    <a class="button error" @click="clickDelete()">Löschen</a>
                    <a class="button warning" @click="clickBack()">Zurück</a>
                </div>

            </div>

            <div class="form-entry-component-form-section" v-for="formGroup in form.config">

                <div class="form-entry-component-form-section-group-headline">{{ formGroup.name }}</div>

                <div class="row"></div>

                <template v-for="field in formGroup.fields">

                    <template v-if="field"></template>
                    <div class="row">
                        <div class="col-md-6" v-if="field.type === 'textarea'">
                            <label>{{ field.name }}</label>
                            <textarea type="text" class="form-control" :value="getFieldData(field)" readonly></textarea>
                        </div>
                        <div class="col-md-4" v-else-if="field.type === 'boolean'">
                            <label>{{ field.name }}</label>
                            <input type="text" class="form-control" :value="getFieldData(field) ? 'Ja' : 'Nein'" readonly>
                        </div>
                        <div class="col-md-2" v-else-if="field.type === 'image'">
                            <label>{{ field.name }}</label>
                            <a :href="'/api/v1/files/view/'+getFieldData(field)?.id+'.'+getFieldData(field)?.extension"
                               v-if="getFieldData(field)?.id" target="_blank" class="button primary">Bild ansehen</a>
                            <span v-else>Kein Bild vorhanden.</span>
                        </div>
                        <div class="col-md-2" v-else-if="field.type === 'file'">
                            <label>{{ field.name }}</label>
                            <a :href="'/api/v1/files/download/'+getFieldData(field)?.id+'.'+getFieldData(field)?.extension"
                               v-if="getFieldData(field)?.id" target="_blank" class="button primary">Datei herunterladen</a>
                            <span v-else>Keine Datei vorhanden.</span>
                        </div>
                        <div class="col-md-4" v-else>
                            <label>{{ field.name }}</label>
                            <input type="text" class="form-control" :value="getFieldData(field)" readonly>
                        </div>
                    </div>
                </template>

                <div class="row"></div>
            </div>

        </div>

        <transition name="fade">
            <Modal v-if="modal" :config="modal"></Modal>
        </transition>

    </div>

</template>

<script>
import { mapState } from 'vuex';
import Modal from './Modal';

export default {
    data() {
        return {
            formEntry: {
                language: '',
                content: {},
                form: null,
            },
            form: {},
            modal: null,
        };
    },
    components: {
        Modal,
    },
    computed: {
        ...mapState({
            selectedForm: state => state.forms.form,
            selectedFormEntry: state => state.formEntries.formEntry,
        }),
        /*formConfig () {
            return {
                ...formConfig
            };
        },*/
    },
    methods: {
        clickDelete () {
            console.log(this.formEntry.id);
            this.modal = {
                title: 'Eintrag löschen',
                description: 'Sind Sie sicher dass Sie diesen Eintrag unwiderruflich löschen möchten?',
                actions: [
                    {
                        label: 'Endgültig löschen',
                        class: 'error',
                        onClick: () => {
                            this.$store.dispatch('formEntries/delete', this.formEntry.id).then(() => {
                                this.$router.push('/forms/'+this.form.id+'/entries');
                            });
                        }
                    },
                    {
                        label: 'Abbrechen',
                        class: 'warning',
                        onClick: () => {
                            this.modal = null;
                        }
                    }
                ],
            };
        },
        clickBack () {
            this.$router.push('/forms/'+this.form.id+'/entries');
        },
        reload() {
            if(this.$route.params.id) {
                this.$store.commit('forms/set', {});
                this.$store.dispatch('forms/load', this.$route.params.id).then(() => {
                    this.form = {...this.selectedForm};
                });

                this.$store.dispatch('formEntries/load', this.$route.params.entryId).then(() => {
                    this.formEntry = {...this.selectedFormEntry};
                });
            }
        },
        getFieldData(field) {
            let fieldData = null;

            fieldData = this.formEntry.content[field.identifier] || null;

            return fieldData;
        },
    },
    created () {
        this.reload();
    }
}
</script>