<template>

    <div class="form-entry-component">

        <div class="form-entry-component-form">

            <div class="form-entry-component-form-header">

                <h3>Formulareintrag</h3>

                <div class="backend-component-loader" :class="{visible: isLoading}">
                    <div class="backend-component-loader-text">
                        Formular wird übersetzt ({{ locale.toUpperCase() }})...
                    </div>
                </div>

                <div class="form-entry-component-form-header-actions">
                    <a class="button success" @click="locale = ''" :class="{primary: locale === ''}">Originalsprache anzeigen</a>
                    <a @click="locale = 'de'; clickTranslate()" class="button" :class="{primary: locale === 'de'}" v-if="formEntry.language !== 'de'">DE</a>
                    <a @click="locale = 'fr'; clickTranslate()" class="button" :class="{primary: locale === 'fr'}" v-if="formEntry.language !== 'fr'">FR</a>
                    <a @click="locale = 'it'; clickTranslate()" class="button" :class="{primary: locale === 'it'}" v-if="formEntry.language !== 'it'">IT</a>
                    <a class="button error" @click="clickDelete()">Löschen</a>
                    <a class="button warning" @click="clickBack()">Zurück</a>
                </div>

            </div>

            <div class="form-entry-component-form-section" v-for="formGroup in form.config">

                <div class="form-entry-component-form-section-group-headline">{{ formGroup.name }}</div>

                <div class="row"></div>

                <template v-for="field in formGroup.fields">
                    <div class="row">
                        <div class="col-md-6" v-if="field.type === 'textarea'">
                            <template v-if="!locale">
                                <label>{{ field.name }}</label>
                                <textarea type="text" class="form-control" :value="getFieldData(field)" readonly></textarea>
                            </template>
                            <template v-else>
                                <label>{{ field.name }} (Übersetzung {{ locale.toUpperCase() }})</label>
                                <textarea type="text" class="form-control" :value="translateField(field.identifier)" readonly></textarea>
                            </template>
                        </div>
                        <div class="col-md-4" v-else-if="field.type === 'boolean'">
                            <template v-if="!locale">
                                <label>{{ field.name }}</label>
                            </template>
                            <template v-else>
                                <label>{{ field.name }} (Übersetzung {{ locale.toUpperCase() }})</label>
                            </template>
                            <input type="checkbox" :checked="getFieldData(field)" readonly>
                        </div>
                        <div class="col-md-8" v-else-if="field.type === 'image'">
                            <label>{{ field.name }}</label>
                            <template v-if="getFieldData(field)?.length">
                                <div class="form-entry-component-form-section-group">
                                    <template v-for="(image, index) in getFieldData(field)">
                                        <a :href="'/api/v1/files/view/'+image.id+'.'+image.extension" v-if="image?.id"
                                           target="_blank" class="button primary">{{ getFieldData(field).filter(data => data?.id)?.length > 1 ? (index + 1) + '. ' : '' }}Bild ansehen</a>
                                    </template>
                                </div>
                            </template>
                            <span v-else>Keine Bilder vorhanden.</span>
                        </div>
                        <div class="col-md-8" v-else-if="field.type === 'file'">
                            <label>{{ field.name }}</label>
                            <template v-if="getFieldData(field)?.length">
                                <div class="form-entry-component-form-section-group">
                                    <template v-for="(file, index) in getFieldData(field)">
                                        <a :href="'/api/v1/files/download/'+file.id+'.'+file.extension" v-if="file?.id"
                                           class="button primary">{{ getFieldData(field).filter(data => data.id)?.length > 1 ? (index + 1) + '. ' : '' }}Datei herunterladen</a>
                                    </template>
                                </div>
                            </template>
                            <span v-else>Keine Dateien vorhanden.</span>
                        </div>
                        <div class="col-md-4" v-else-if="field.type === 'list'">
                            <template v-if="!locale">
                                <label>{{ field.name }}</label>
                                <div class="form-entry-component-form-section-group" v-if="getFieldData(field)?.length">
                                    <input type="text" class="form-control" :value="element" v-for="element in getFieldData(field)" readonly>
                                </div>
                                <span v-else>-</span>
                            </template>
                            <template v-else>
                                <label>{{ field.name }} (Übersetzung {{ locale.toUpperCase() }})</label>
                                <div class="form-entry-component-form-section-group" v-if="getFieldData(field)?.length">
                                    <input type="text" class="form-control" :value="element" v-for="element in formEntry.translations[locale][field.identifier]" readonly>
                                </div>
                                <span v-else>-</span>
                            </template>
                        </div>
                        <div class="col-md-8" v-else-if="field.type === 'list_amount'">
                            <label>{{ field.name }}</label>
                            <div class="form-entry-component-form-section-row" v-for="element of getFieldData(field)">
                                <span>{{ element.label }}</span>
                                <input type="number" class="form-control" :value="element.value" readonly>
                            </div>
                        </div>
                        <div class="col-md-4" v-else>
                            <template v-if="!locale">
                                <label>{{ field.name }}</label>
                                <input type="text" class="form-control" :value="getFieldData(field)" readonly>
                            </template>
                            <template v-else>
                                <label>{{ field.name }} (Übersetzung {{ locale.toUpperCase() }})</label>
                                <input type="text" class="form-control" :value="translateField(field.identifier)" readonly>
                            </template>
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
import { mapState, mapGetters } from 'vuex';
import Modal from './Modal';

export default {
    data() {
        return {
            locale: '',
            formEntry: {
                language: '',
                content: {},
                form: null,
                translations: {
                    de: {},
                    fr: {},
                    it: {},
                },
            },
            form: {},
            modal: null,
            fieldBooleanMapping: {
                de: {
                    true: 'Ja',
                    false: 'Nein',
                },
                fr: {
                    true: 'Oui',
                    false: 'Non',
                },
                it: {
                    true: 'Sì',
                    false: 'No',
                },
            },
            isLoading: false,
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
    },
    methods: {
        clickDelete () {
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
                    this.isLoading = false;
                });
            }
        },
        getFieldData(field) {
            let fieldData = null;

            fieldData = this.formEntry.content[field.identifier] || null;

            return fieldData;
        },
        getFieldDataBoolean(field) {
            let value = this.getFieldData(field) ? 'true' : 'false';
            let lang = this.locale || this.formEntry.language;

            if(!lang || !this.fieldBooleanMapping[lang]) {
                return '';
            }

            return this.fieldBooleanMapping[lang][value] || '';
        },
        async clickTranslate() {
            if(this.formEntry.translations && this.formEntry.translations[this.locale]) {
                return;
            }

            this.isLoading = true;

            await this.$store.dispatch('formEntries/translate', { ...this.formEntry, selectedTranslation: this.locale });

            this.reload();
        },
        translateField(identifier) {
            if(!this.formEntry.translations || !this.formEntry.translations[this.locale]) {
                return '';
            }

            return this.formEntry.translations[this.locale][identifier] || '';
        },
    },
    created () {
        this.reload();
    }
}
</script>