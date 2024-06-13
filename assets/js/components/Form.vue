<template>

    <div class="form-component">

        <div class="form-component-form">

            <div class="form-component-form-header">

                <h3 v-if="form.id">Formular bearbeiten</h3>
                <h3 v-if="!form.id">Formular erfassen</h3>

                <div class="form-component-form-header-actions">
                    <a class="button warning" @click="form.isPublic = true" v-if="!form.isPublic">Entwurf</a>
                    <a class="button success" @click="form.isPublic = false" v-if="form.isPublic">Öffentlich</a>
                    <a @click="locale = 'de'" class="button" :class="{primary: locale === 'de'}">DE</a>
                    <a @click="locale = 'fr'" class="button" :class="{primary: locale === 'fr'}">FR</a>
                    <a @click="locale = 'it'" class="button" :class="{primary: locale === 'it'}">IT</a>
                    <a @click="locale = 'en'" class="button" :class="{primary: locale === 'en'}">EN</a>
                    <a @click="locale = 'si'" class="button" :class="{primary: locale === 'si'}">SI</a>
                    <a class="button error" @click="clickDelete()" v-if="form.id">Löschen</a>
                    <a class="button warning" @click="clickCancel()">Abbrechen</a>
                    <a class="button primary" @click="clickSave()">Speichern</a>
                </div>
            </div>

            <div class="form-component-form-section">

                <div class="row">
                    <div class="col-md-4" v-if="locale === 'de'">
                        <label for="title">Bezeichnung</label>
                        <input id="title" type="text" class="form-control" v-model="form.name" :placeholder="translateField(form, 'name', locale)">
                    </div>
                    <div class="col-md-4" v-else>
                        <label for="title">Bezeichnung (Übersetzung {{ locale.toUpperCase() }})</label>
                        <input id="title" type="text" class="form-control" v-model="form.translations[locale].name" :placeholder="translateField(form, 'name', locale)">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <label for="recipients">Mailempfänger (über Zeilenumbruch getrennt)</label>
                        <textarea class="form-control" rows="2" v-model="form.recipients"></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6" v-if="locale === 'de'">
                        <label for="thankYou">Danksagung</label>
                        <ckeditor id="thankYou" :editor="editor" :config="editorConfig"
                                  v-model="form.thankYou" :placeholder="translateField(form, 'thankYou', locale)"></ckeditor>
                    </div>
                    <div class="col-md-6" v-else>
                        <label for="thankYou">Danksagung (Übersetzung {{ locale.toUpperCase() }})</label>
                        <ckeditor id="thankYou" :editor="editor" :config="editorConfig"
                                  v-model="form.translations[locale].thankYou" :placeholder="translateField(form, 'thankYou', locale)"></ckeditor>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <label>Felderkonfiguration</label>

                        <template v-for="(formGroup, index) in form.config">

                            <div class="form-component-form-section-group">

                                <div class="row">
                                    <div class="col-md-12" v-if="locale === 'de'">
                                        <label>Gruppe</label>
                                        <input type="text" class="form-control" v-model="form.config[index].name" :placeholder="translateField(formGroup, 'name', locale)">
                                    </div>
                                    <div class="col-md-12" v-else>
                                        <label>Gruppe (Übersetzung {{ locale.toUpperCase() }})</label>
                                        <input type="text" class="form-control" :value="form.config[index].translations[locale] || ''" @input="setFormGroupTranslation(index, $event.target.value)" :placeholder="translateField(formGroup, 'name', locale)">
                                    </div>
                                </div>

                            </div>

                            <div class="form-component-form-section-group">

                                <div class="form-component-form-section-group-headline">Felder:</div>

                                <div class="row"></div>

                                <template v-for="(field, fieldIndex) in form.config[index].fields">

                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Feldtyp</label>
                                            <div class="select-wrapper">
                                                <select class="form-control" :value="form.config[index].fields[fieldIndex].type || 'text'" @change="form.config[index].fields[fieldIndex] = { ...formConfig[$event.target.value], translations: {} }">
                                                    <option :value="fieldType" v-for="fieldType in formConfig.body.formGroups.formGroup.fields.field.types">{{ formConfig[fieldType].label }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Identifikation</label>
                                            <input type="text" class="form-control" v-model="field.identifier">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4" v-if="locale === 'de'">
                                            <label>Label</label>
                                            <input type="text" class="form-control" v-model="field.name" :placeholder="translateField(field, 'name', locale)">
                                        </div>
                                        <div class="col-md-4" v-else>
                                            <label>Label (Übersetzung {{ locale.toUpperCase() }})</label>
                                            <input type="text" class="form-control" :placeholder="translateField(field, 'name', locale)"
                                                   :value="form.config[index].fields[fieldIndex].translations[locale]?.name || ''" @input="setFormFieldTranslation(index, fieldIndex, 'name', $event.target.value)">
                                        </div>
                                        <div class="col-md-6" v-if="locale === 'de'">
                                            <label>Hinweis</label>
                                            <input type="text" class="form-control" v-model="field.note" :placeholder="translateField(field, 'note', locale)">
                                        </div>
                                        <div class="col-md-6" v-else>
                                            <label>Hinweis (Übersetzung {{ locale.toUpperCase() }})</label>
                                            <input type="text" class="form-control" :placeholder="translateField(field, 'note', locale)"
                                                   :value="form.config[index].fields[fieldIndex].translations[locale]?.note || ''" @input="setFormFieldTranslation(index, fieldIndex, 'note', $event.target.value)">
                                        </div>
                                    </div>

                                    <template v-if="field.type === 'text' || field.type === 'textarea'">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <label>Zeichenanzahl (min)</label>
                                                <input type="number" class="form-control" min="1" v-model="field.min">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Zeichenanzahl (max)</label>
                                                <input type="number" class="form-control" v-model="field.max">
                                            </div>
                                            <template v-if="field.type === 'textarea'">
                                                <div class="col-md-2">
                                                    <label>Feldhöhe</label>
                                                    <input type="number" class="form-control" min="1" v-model="field.rows">
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template v-if="field.type === 'select'">
                                        <div class="row">
                                            <div class="col-md-12" v-if="locale === 'de'">
                                                <label>Optionen (über Zeilenumbruch getrennt)</label>
                                                <textarea class="form-control" rows="4" v-model="field.options" :placeholder="translateField(field, 'options', locale)"></textarea>
                                            </div>
                                            <div class="col-md-12" v-else>
                                                <label>Optionen (Übersetzung {{ locale.toUpperCase() }})</label>
                                                <textarea class="form-control" rows="4" :placeholder="translateField(field, 'options', locale)"
                                                          :value="form.config[index].fields[fieldIndex].translations[locale]?.options || ''" @input="setFormFieldTranslation(index, fieldIndex, 'options', $event.target.value)"></textarea>
                                            </div>
                                        </div>
                                    </template>

                                    <div class="row">
                                        <div class="col-md-4 form-component-form-checkbox">
                                            <input type="checkbox" v-model="field.required">
                                            <label>Erforderlich</label>
                                        </div>
                                        <template v-if="field.type !== 'boolean' && field.type !== 'image' && field.type !== 'file'">
                                            <div class="col-md-4 form-component-form-checkbox">
                                                <input type="checkbox" v-model="field.visible">
                                                <label>In Liste ausgeben</label>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="row"></div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="button warning" @click="form.config[index].fields.splice(form.config[index].fields.indexOf(field), 1)">Feld entfernen</div>
                                        </div>
                                    </div>

                                    <hr>

                                </template>

                                <div class="form-component-form-section-form-group-field">
                                    <div class="button primary" @click="form.config[index].fields.push({ ...formConfig.text, translations: {} })">Feld hinzufügen</div>
                                </div>

                                <div class="row"></div>

                            </div>

                            <div class="form-component-form-section-group">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="button warning" @click="form.config.splice(form.config.indexOf(formGroup), 1)">Gruppe entfernen</div>
                                    </div>
                                </div>
                            </div>

                        </template>

                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-component-form-section">
                            <div class="button primary" @click="form.config.push({ name: '', fields: [], translations: {} })">Gruppe hinzufügen</div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <transition name="fade">
            <Modal v-if="modal" :config="modal"></Modal>
        </transition>

    </div>

</template>

<script>
import { mapState } from 'vuex';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import Modal from './Modal';
import formConfig from '../../../config/form_fields.yaml';
import { translateField } from '../utils/filters';

export default {
    data() {
        return {
            locale: 'de',
            form: {
                isPublic: false,
                name: '',
                recipients: '',
                thankYou: '',
                config: [],
                translations: {
                    fr: {
                        groups: {},
                        fields: {}
                    },
                    it: {
                        groups: {},
                        fields: {}
                    },
                    en: {
                        groups: {},
                        fields: {}
                    },
                    si: {
                        groups: {},
                        fields: {}
                    },
                },
            },
            modal: null,
            editor: ClassicEditor,
            editorConfig: {
                basicEntities: false,
                toolbar: {
                    items: [
                        'heading',
                        '|',
                        'bold',
                        'italic',
                        'link',
                        '|',
                        'numberedList',
                        'bulletedList',
                        'insertTable',
                        '|',
                        'undo',
                        'redo',
                    ]
                }
            },
        };
    },
    components: {
        Modal,
    },
    computed: {
        ...mapState({
            selectedForm: state => state.forms.form,
        }),
        formConfig () {
            return {
                ...formConfig
            };
        },
    },
    methods: {
        translateField,
        clickDelete () {
            this.modal = {
                title: 'Eintrag löschen',
                description: 'Sind Sie sicher dass Sie diesen Eintrag unwiderruflich löschen möchten?',
                actions: [
                    {
                        label: 'Endgültig löschen',
                        class: 'error',
                        onClick: () => {
                            this.$store.dispatch('forms/delete', this.form.id).then(() => {
                                this.$router.push('/forms');
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
        clickCancel () {
            this.$router.push('/forms');
        },
        clickSave() {
            if(this.form.id) {
                return this.$store.dispatch('forms/update', this.form).then(() => {
                    this.$router.push('/forms');
                });
            }

            this.$store.dispatch('forms/create', this.form).then(() => {
                this.$router.push('/forms');
            });
        },
        reload() {
            if(this.$route.params.id) {
                this.$store.commit('forms/set', {});
                this.$store.dispatch('forms/load', this.$route.params.id).then(() => {
                    this.form = {...this.selectedForm};
                });
            }
        },
        translate(property, context) {
            if (this.locale === 'de') {
                return context[property] || context.translations.fr[property] || context.translations.it[property] || context.translations.en[property] || context.translations.si[property];
            }
            if (this.locale === 'fr') {
                return context.translations.fr[property] || context[property] || context.translations.it[property];
            }
            if (this.locale === 'it') {
                return context.translations.it[property] || context[property] || context.translations.fr[property];
            }
            if (this.locale === 'en') {
                return context.translations.en[property] || context[property] || context.translations.fr[property];
            }
            if (this.locale === 'si') {
                return context.translations.si[property] || context[property] || context.translations.en[property];
            }
            return context[property];
        },
        isTranslationModeEnabled() {
            return this.locale !== 'de';
        },
        setFormGroupTranslation(index, value) {
            if(Array.isArray(this.form.config[index].translations)) {
                this.form.config[index].translations = {};
            }

            this.form.config[index].translations[this.locale] = value;
        },
        setFormFieldTranslation(index, fieldIndex, identifier, value) {
            if(Array.isArray(this.form.config[index].fields[fieldIndex].translations)) {
                this.form.config[index].fields[fieldIndex].translations = {};
            }

            if(!this.form.config[index].fields[fieldIndex].translations[this.locale]) {
                this.form.config[index].fields[fieldIndex].translations[this.locale] = {};
            }

            this.form.config[index].fields[fieldIndex].translations[this.locale][identifier] = value;
        }
    },
    created () {
        this.reload();
    }
}
</script>