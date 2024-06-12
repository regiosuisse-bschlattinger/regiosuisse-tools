<template>

    <div class="embed-form" :class="[$env.INSTANCE_ID+'-form', {'is-responsive': responsive}]">

        <div class="embed-form-wrapper">

            <div class="embed-form-content" v-if="!formIsSent && form?.id">

                <h5>{{ $t('Mit * gekennzeichnete Felder sind Pflichtfelder.', locale) }}</h5>

                <div class="embed-form-form-group" v-for="(formGroup, index) of form.config">

                    <h3 v-if="formGroup.name">{{ translateField(formGroup, 'name', locale) }}</h3>

                    <template v-for="field in formGroup.fields">
                        <template v-if="field.type === 'text'">
                            <div class="embed-form-fields-input">
                                <label>{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                                <input type="text" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }" v-model="formEntry.content[field.identifier]" @input="validateFormData(field, $event.target.value)">
                            </div>
                        </template>
                        <template v-else-if="field.type === 'textarea'">
                            <div class="embed-form-fields-textarea">
                                <label>{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                                <textarea :rows="field.rows || 3" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }" v-model="formEntry.content[field.identifier]" @input="validateFormData(field, $event.target.value)"></textarea>
                            </div>
                        </template>
                        <template v-else-if="field.type === 'email'">
                            <div class="embed-form-fields-input">
                                <label>{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                                <input type="text" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }" v-model="formEntry.content[field.identifier]" @input="validateFormData(field, $event.target.value)">
                            </div>
                        </template>
                        <template v-else-if="field.type === 'boolean'">
                            <div class="embed-form-fields-boolean">
                                <input type="checkbox" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }" v-model="formEntry.content[field.identifier]" @change="validateFormData(field, $event.target.checked)">
                                <label>{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                            </div>
                        </template>
                        <template v-else-if="field.type === 'select'">
                            <div class="embed-form-fields-select">
                                <label>{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                                <div class="embed-form-fields-select-field">
                                    <input type="text" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }" readonly :value="formEntry.content[field.identifier] || null" :placeholder="$t('Bitte wählen', locale)" @click.stop="clickToggleSelect(field.identifier)">
                                    <div class="embed-form-fields-select-icon"
                                         :class="{'is-active': activeSelect === field.identifier}"></div>
                                </div>

                                <transition name="embed-form-fields-select-options" mode="out-in">
                                    <div class="embed-form-fields-select-options" v-if="activeSelect === field.identifier">
                                        <div class="embed-form-fields-select-options-item"
                                             v-for="option in translateField(field, 'options', locale).split('\n')"
                                             :class="{ 'is-selected': formEntry.content[field.identifier] === option }"
                                             @click.stop="formEntry.content[field.identifier] = option; clickToggleSelect(field.identifier); validateFormData(field, option)">
                                            {{ option }}
                                        </div>
                                    </div>
                                </transition>

                            </div>
                        </template>
                        <template v-else-if="field.type === 'image'">
                            <div class="embed-form-fields-image">
                                <div class="embed-form-fields-image-field">
                                    <input type="file" :id="'upload_'+field.identifier" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }"
                                           :ref="'upload_'+field.identifier" @change="clickUploadFile(field.identifier); validateFormData(field, $event.target.value)"
                                           :accept="'.jpg,.png,.gif'">
                                    <label :for="'upload_'+field.identifier">
                                        <span v-if="formEntry.content[field.identifier]">✔</span>
                                        <span v-else>+</span>
                                    </label>
                                </div>
                                <div class="embed-form-fields-image-text">
                                    <label @click="clickRemoveFile(field.identifier, true)">{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                                    <div v-if="formEntry.content[field.identifier]"><i>{{ formEntry.content[field.identifier].name }}</i><span @click="clickRemoveFile(field.identifier)">[Entfernen]</span></div>
                                </div>
                            </div>
                        </template>
                        <template v-else-if="field.type === 'file'">
                            <div class="embed-form-fields-file">
                                <div class="embed-form-fields-file-field">
                                    <input type="file" :id="'upload_'+field.identifier" :class="{ 'has-error': !isValid && errors.find(error => error.field === field.identifier) }"
                                           :ref="'upload_'+field.identifier" @change="clickUploadFile(field.identifier); validateFormData(field, $event.target.value)"
                                           :accept="'.pdf,.doc,.docx,.zip,.rar,.7zip,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'">
                                    <label :for="'upload_'+field.identifier">
                                        <span v-if="formEntry.content[field.identifier]">✔</span>
                                        <span v-else>+</span>
                                    </label>
                                </div>
                                <div class="embed-form-fields-file-text">
                                    <label @click="clickRemoveFile(field.identifier, true)">{{ translateField(field, 'name', locale) }}<span class="field-asterisk" v-if="field.required">*</span> <span class="field-note">{{ translateField(field, 'note', locale) }}</span></label>
                                    <div v-if="formEntry.content[field.identifier]"><i>{{ formEntry.content[field.identifier].name }}</i><span @click="clickRemoveFile(field.identifier)">[Entfernen]</span></div>
                                </div>
                            </div>
                        </template>
                    </template>

                </div>

            </div>

            <div class="embed-form-actions" v-if="!isLoading && !formIsSent">

                <div class="embed-form-actions-item">

                    <a class="embed-form-button is-disabled" v-if="formIsSending">{{ $t('Formular wird versendet...', locale) }}</a>
                    <a class="embed-form-button" @click="clickSubmit" v-else>{{ $t('Absenden', locale) }}</a>

                </div>

            </div>

            <div class="embed-form-content" v-if="form && formIsSent">

                <div class="embed-form-thank-you">

                    <svg class="embed-form-thank-you-icon" xmlns="http://www.w3.org/2000/svg" width="279" height="279" viewBox="0 0 279 279">
                        <g id="Group_574" data-name="Group 574" transform="translate(-9953 -1477)">
                            <path id="Path_8666" data-name="Path 8666" d="M139.5,0A139.5,139.5,0,1,1,0,139.5,139.5,139.5,0,0,1,139.5,0Z" transform="translate(9953 1477)" fill="#eaf6ea"/>
                            <path id="Path_8666_-_Outline" data-name="Path 8666 - Outline" d="M139.5,10A129.5,129.5,0,1,0,269,139.5,129.647,129.647,0,0,0,139.5,10m0-10A139.5,139.5,0,1,1,0,139.5,139.5,139.5,0,0,1,139.5,0Z" transform="translate(9953 1477)" fill="#fff"/>
                            <g id="check-fat-duotone" transform="translate(9974.328 1480.761)">
                                <path id="Vector" d="M230.275,108.916,104.984,234.208a7.807,7.807,0,0,1-11.046,0L24.071,163.951a7.806,7.806,0,0,1,0-11.036L47.49,129.5a7.8,7.8,0,0,1,11.046,0l41.315,40.085,95.959-94.74a7.807,7.807,0,0,1,11.046,0l23.419,23.029a7.808,7.808,0,0,1,0,11.046Z" transform="translate(-9.032 -9.072)" fill="#7e0ab9"/>
                                <path id="Vector_2" d="M226.727,83.237l-23.419-22.99a15.613,15.613,0,0,0-22.043,0L90.819,149.581l-35.8-34.738a15.613,15.613,0,0,0-22.033.049L9.564,138.31a15.613,15.613,0,0,0,0,22.063L79.451,230.63a15.614,15.614,0,0,0,22.082,0L226.776,105.358a15.614,15.614,0,0,0-.049-22.121ZM90.448,219.613,20.562,149.356l23.419-23.419a.578.578,0,0,1,.078.078l41.325,40.1a7.807,7.807,0,0,0,10.919,0L192.36,71.293l23.36,23.029Z" transform="translate(0 0)" fill="#fff"/>
                            </g>
                        </g>
                    </svg>

                    <div class="embed-form-thank-you-text" v-html="translateField(form, 'thankYou', locale)"></div>

                </div>

            </div>

        </div>
        
    </div>

</template>

<script>

import {mapState} from 'vuex';
import { translateField } from '../utils/filters';
import {trackDevice, trackPageView} from '../utils/logger';

export default {
    data() {
        return {
            isLoading: false,
            formIsSending: false,
            formIsSent: false,
            form: null,
            formEntry: {
                content: {},
                form: null,
            },
            activeSelect: null,
            isValid: true,
            errors: [],
            window,
        };
    },
    computed: {
        ...mapState({
            selectedForm: state => state.forms.form,
        }),
        locale () {
            return this.$clientOptions?.locale || 'de';
        },
        responsive () {
            return this.$clientOptions?.responsive ?? true;
        },
    },
    methods: {
        translateField,
        reload() {
            const app = document.getElementById('app');
            const formId = app.attributes['data-id'].value;

            if (formId) {
                this.$store.commit('forms/set', {});
                this.$store.dispatch('forms/load', formId).then(() => {
                    this.form = {...this.selectedForm};
                    this.formEntry.form = this.form;

                    let fields = [];
                    for(let formGroup of this.form.config) {
                        for(let field of formGroup.fields) {
                            fields.push(field);
                        }
                    }

                    for(let field of fields) {
                        this.validateFormData(field, this.formEntry.content[field.identifier] || null);
                    }
                });
            }
        },
        clickToggleSelect(identifier) {
            if(this.activeSelect === identifier) {
                return this.activeSelect = null;
            }

            this.activeSelect = identifier;
        },
        clickUploadFile (identifier) {
            let files = this.$refs['upload_'+identifier][0].files;

            this.formEntry.content[identifier] = null;

            if(!files.length) {
                return;
            }

            let file = files[0];

            let reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => {

                if(file.size > 2e+7) {
                    return alert('Die ausgewählte Datei "'+file.name+'" überschreitet das Uploadlimit von 20 MB.');
                }

                let item = {
                    name: file.name,
                    description: '',
                    copyright: '',
                    data: null,
                    mimeType: file.type,
                    extension: file.name.split('.')[1] ? file.name.split('.')[file.name.split('.').length-1] : '',
                    loading: true,
                };

                item.extension = item.extension.toLowerCase();

                this.resizeImage(reader.result, file.type, (image) => {

                    item.data = image;

                    this.$store.dispatch('files/create', item).then((file) => {
                        return this.formEntry.content[identifier] = file;
                    }).catch(err => {
                        alert('File could not be uploaded.')
                    }) ;
                });
            };
        },
        resizeImage(base64, mimeType, onComplete) {
            if(!['image/jpeg', 'image/png', 'image/gif'].includes(mimeType)) {
                return onComplete(base64);
            }

            let maxWidth = 2000;
            let maxHeight = 2000;
            const image = new Image();
            image.src = base64;
            image.onload = () => {
                let ratio = image.height / image.width;
                let width = maxWidth;
                let height = Math.floor(maxWidth * ratio);
                if(ratio > 1) {
                    ratio = image.width / image.height;
                    width = Math.floor(maxHeight * ratio);
                    height = maxHeight;
                }
                let canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                let context = canvas.getContext('2d');
                context.drawImage(image, 0, 0, width, height);
                return onComplete(canvas.toDataURL(mimeType, .9));
            };
        },
        clickRemoveFile(identifier) {
            let files = this.$refs['upload_'+identifier][0].files;

            if(!files.length) {
                return;
            }

            this.$refs['upload_'+identifier][0].value = null;
            this.formEntry.content[identifier] = null;
        },
        validateFormData(field, data) {
            let errorIndex = this.errors.indexOf(this.errors.find(error => error.field === field.identifier));

            if(errorIndex >= 0) {
                this.errors.splice(errorIndex, 1);
            }

            if(field.required && (!data || data === false)) {
                return this.errors.push({ field: field.identifier });
            }

            if((field.type === 'text' || field.type === 'textarea') && data?.length) {
                if(field.min && data.length < field.min) {
                    return this.errors.push({ field: field.identifier });
                }
                if(field.max && data.length > field.max) {
                    return this.errors.push({ field: field.identifier });
                }
            }

            if(field.type === 'email') {
                if(!/\S+@\S+\.\S+/.test(data)) {
                    return this.errors.push({ field: field.identifier });
                }
            }
        },
        resetFormData() {

            let originalFormData = {
                firstName: '',
                lastName: '',
                email: '',
                phone: '',
                company: '',
                request: '',
            };

            this.$store.commit('customContactForms/setFormData', originalFormData);
            this.privacy = false;
        },
        clickSubmit() {
            if(this.errors.length) {
                return this.isValid = false;
            }

            this.isValid = true;

            this.formEntry.language = this.locale;
            this.formIsSending = true;

            this.$store.dispatch('formEntries/create', this.formEntry).then((response) => {

                this.formEntry = {
                    ...this.formEntry,
                    content: {},
                };

                this.formIsSending = false;
                this.formIsSent = true;

            }).catch((err) => {
                this.errors = err.response.data;
                this.isValid = false;
                this.formIsSending = false;
            });
        }
    },

    created() {
        this.reload();
    },

    mounted() {
        if(this.history) {
            window.addEventListener('popstate', this.popState);
        }

        if(!this.disableTelemetry) {
            trackDevice();
            trackPageView();
        }
    },

    beforeUnmount() {
        window.removeEventListener('popstate', this.popState);
    },

    beforeRouteLeave(to, from, next) {
        if(Object.keys(this.formEntry.content).length > 0) {
            if (window.confirm('Are you sure you want to leave? Any unsaved changes will be lost.')) {
                next();
            } else {
                next(false);
            }
        } else {
            next();
        }
    },

};

</script>