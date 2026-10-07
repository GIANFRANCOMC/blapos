<template>
    <a
        href="javascript:void(0)"
        @click="openModal()"
        :class="triggerClasses"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Agregar cliente">
        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
        <span v-if="triggerLabel" v-text="triggerLabel"></span>
    </a>

    <Teleport to="body">
        <!-- Modal Create -->
        <div
            class="modal fade br-entity-modal br-modal-shell br-quick-create-modal"
            :id="forms[entity].createUpdate.extras.modals.default.id"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            tabindex="-1"
            aria-hidden="true"
            role="dialog"
            aria-modal="true"
            @hidden.bs.modal="handleClose"
            @shown.bs.modal="handleShown">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content br-entity-modal br-modal-shell">
                <div class="modal-header br-entity-modal__header br-modal-shell__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Gestión de clientes</p>
                        <h2 class="modal-title br-entity-modal__title" v-text="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"></h2>
                    </div>
                    <button type="button" class="br-modal-close br-modal-shell__close" :aria-label="MODULE.texts.modal.close" @click="closeModal">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body br-modal-shell__body br-quick-create-modal__body">
                    <form @submit.prevent="saveEntity">
                        <div class="row g-3">
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.identityDocumentType"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.identity_document_type_id"
                                xl="6"
                                lg="6">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.identity_document_type"
                                        :options="identityDocumentTypes"
                                        :class="config.forms.classes.select2"
                                        @close="tooltips({show: true, time: 500})"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputText
                                v-model="forms[entity].createUpdate.data.document_number"
                                hasDiv
                                :title="MODULE.texts.form.documentNumber"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                :maxlength="documentNumberMaxLength"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.document_number"
                                xl="6"
                                lg="6">
                                <template v-slot:inputGroupPrepend v-if="!isUpdate">
                                    <template v-if="isDocumentTypeSearchable">
                                        <button class="br-btn br-btn-search waves-effect" type="button" @click="searchDocumentNumber" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.searchDocumentTooltip">
                                            <span>Buscar</span>
                                        </button>
                                    </template>
                                </template>
                            </InputText>
                            <InputText
                                v-model="forms[entity].createUpdate.data.name"
                                hasDiv
                                :title="MODULE.texts.form.name"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.name"
                                xl="6"
                                lg="6"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.email"
                                @input="onEmailInput"
                                hasDiv
                                :title="MODULE.texts.form.email"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.email"
                                xl="6"
                                lg="6"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.phone_number"
                                hasDiv
                                :title="MODULE.texts.form.phoneNumber"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="15"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.phone_number"
                                xl="4"
                                lg="4"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.gender"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.gender"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.gender"
                                        :options="genders"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputDate
                                v-model="forms[entity].createUpdate.data.birthdate"
                                hasDiv
                                :title="MODULE.texts.form.birthdate"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.birthdate"
                                xl="4"
                                lg="4"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.status"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.status"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.status"
                                        :options="statuses"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <div class="col-12">
                                <div class="br-customer-optional-block">
                                    <div class="br-customer-optional-block__header">
                                        <span class="br-customer-optional-block__icon">
                                            <i class="fa-solid fa-kit-medical" aria-hidden="true"></i>
                                        </span>
                                        <div>
                                            <strong v-text="MODULE.texts.form.emergencyBlockTitle"></strong>
                                            <small v-text="MODULE.texts.form.emergencyBlockSubtitle"></small>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <InputText
                                            v-model="forms[entity].createUpdate.data.emergency_contact_name"
                                            hasDiv
                                            :title="MODULE.texts.form.emergencyContactName"
                                            :titleClass="[config.forms.classes.title]"
                                            maxlength="255"
                                            showCharCounter
                                            hasTextBottom
                                            :textBottomInfo="forms[entity].createUpdate.errors?.emergency_contact_name"
                                            xl="6"
                                            lg="6"/>
                                        <InputText
                                            v-model="forms[entity].createUpdate.data.emergency_contact_phone"
                                            hasDiv
                                            :title="MODULE.texts.form.emergencyContactPhone"
                                            :titleClass="[config.forms.classes.title]"
                                            maxlength="50"
                                            showCharCounter
                                            hasTextBottom
                                            :textBottomInfo="forms[entity].createUpdate.errors?.emergency_contact_phone"
                                            xl="6"
                                            lg="6"/>
                                        <InputTextArea
                                            v-model="forms[entity].createUpdate.data.medical_notes"
                                            hasDiv
                                            :title="MODULE.texts.form.medicalNotes"
                                            :titleClass="[config.forms.classes.title]"
                                            maxlength="5000"
                                            rows="3"
                                            hasTextBottom
                                            :textBottomInfo="forms[entity].createUpdate.errors?.medical_notes"
                                            xl="12"
                                            lg="12"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer br-entity-modal__footer br-modal-shell__footer">
                    <button
                        type="button"
                        class="br-btn br-btn-cancel"
                        @click="closeModal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        :class="['br-btn', isUpdate ? 'br-btn-action-edit' : 'br-btn-action-create']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <span v-text="isSaving ? 'Agregando' : MODULE.texts.modal.save"></span>
                    </button>
                </div>
                </div>
            </div>
        </div>
    </Teleport>

</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import { validateOnlyDigits } from "@System/Helpers/ValidationHelpers.js";
import InputDate from "@System/Components/InputDate.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";

const MODULE_CONFIG = {
    entity: "customers",
    pageTitle: "Clientes",
    pageTitleSingular: "Cliente",
    breadcrumbParent: "Gestión de clientes"
};

const FORM_FIELDS = {
    identity_document_type: null,
    document_number: "",
    name: "",
    email: "",
    phone_number: "",
    gender: null,
    birthdate: "",
    emergency_contact_name: "",
    emergency_contact_phone: "",
    medical_notes: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    identity_document_type: {mapToField: "identity_document_type_id"},
    document_number: {trim: true},
    name: {trim: true},
    email: {trim: true},
    phone_number: {trim: true, normalize: true},
    gender: {getCode: true, removeIfEmpty: true},
    birthdate: {normalize: true},
    emergency_contact_name: {trim: true},
    emergency_contact_phone: {trim: true},
    medical_notes: {trim: true},
    status: {getCode: true}
};

const VALIDATION_RULES = {
    identity_document_type: {required: true},
    document_number: {required: true},
    name: {required: true},
    email: {required: false, email: true},
    phone_number: {required: false},
    gender: {required: false},
    birthdate: {required: false},
    emergency_contact_name: {required: false},
    emergency_contact_phone: {required: false},
    medical_notes: {required: false},
    status: {required: true}
};

const ERROR_LABELS = {
    identity_document_type: "Tipo de documento",
    identity_document_type_id: "Tipo de documento",
    document_number: "Número de documento",
    name: "Nombre",
    email: "Correo electrónico",
    phone_number: "Celular",
    gender: "Género",
    birthdate: "Fecha de nacimiento",
    emergency_contact_name: "Contacto de emergencia",
    emergency_contact_phone: "Celular de emergencia",
    medical_notes: "Observaciones médicas",
    status: "Estado"
};

const TEXTS = {
    form: {
        identityDocumentType: "Tipo de documento",
        documentNumber: "Número de documento",
        name: "Nombre",
        email: "Correo electrónico",
        phoneNumber: "Celular",
        gender: "Género",
        birthdate: "Fecha de nacimiento",
        emergencyBlockTitle: "Contacto de emergencia y salud",
        emergencyBlockSubtitle: "Opcional. Úsalo si el equipo necesita actuar rápido ante una incidencia.",
        emergencyContactName: "Contacto de emergencia",
        emergencyContactPhone: "Celular de emergencia",
        medicalNotes: "Observaciones médicas",
        status: "Estado",
        searchDocumentTooltip: "Buscar N° documento"
    },
    modal: {
        close: "Cerrar",
        save: "Agregar cliente"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    validationRules: VALIDATION_RULES,
    errorLabels: ERROR_LABELS,
    texts: TEXTS
};

export default {
    name: "AddCustomer",
    components: {
        InputDate,
        InputSlot,
        InputText,
        InputTextArea
    },
    emits: ["postAction"],
    props: {
        options: {
            type: Object,
            required: false,
            default: () => ({})
        },
        triggerLabel: {
            type: String,
            required: false,
            default: ""
        }
    },
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        const {options, ...restCrudModule} = crudModule;

        return {
            ...restCrudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false
        };

    },
    mounted: async function() {

        this.isInitialized = true;

    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        routeActions() {

            return this.config.entity.routes;

        },
        identityDocumentTypes() {

            return (this.options?.identityDocumentTypes ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        genders() {

            return (this.options?.genders ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        statuses() {

            return (this.options?.statuses ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        isUpdate() {

            return this.isDefined(this.forms[this.entity].createUpdate.data.id);

        },
        triggerClasses() {

            return [
                "br-link",
                "br-quick-create-trigger",
                "br-quick-create-trigger--link",
                "me-2",
                "ms-1",
                "d-inline-flex",
                "align-items-center",
                {"br-quick-create-trigger--icon-only": !this.triggerLabel}
            ];

        },
        modalTitles() {

            return {
                createUpdate: this.forms[this.entity].createUpdate.extras.modals.default.titles
            };

        },
        // Identity document type
        isDocumentTypeSearchable() {

            const documentType = this.forms[this.entity].createUpdate.data.identity_document_type?.data;

            return documentType?.is_searchable === true || documentType?.is_searchable === 1;

        },
        documentNumberMinLength() {

            const documentType = this.forms[this.entity].createUpdate.data.identity_document_type?.data;

            if(documentType?.min_length) {

                return parseInt(documentType.min_length);

            }

            return 1;

        },
        documentNumberMaxLength() {

            const documentType = this.forms[this.entity].createUpdate.data.identity_document_type?.data;

            if(documentType?.max_length) {

                return parseInt(documentType.max_length);

            }

            return 1;

        },
        validationRules() {

            const rules = Utils.cloneJson(this.MODULE.validationRules);

            rules.document_number = {
                ...rules.document_number,
                minLength: this.documentNumberMinLength,
                maxLength: this.documentNumberMaxLength,
                custom: (value) => validateOnlyDigits(value, this.MODULE.errorLabels.document_number)
            };

            return rules;

        }
    },
    methods: {
        onEmailInput(value) {

            this.forms[this.entity].createUpdate.data.email = (value ?? "").toString().toLowerCase();

        },
        // Forms
        openModal() {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            // Set defaults for new record
            entityForms.data.identity_document_type = this.identityDocumentTypes.length > 1 ? this.identityDocumentTypes[1] : null;
            entityForms.data.gender                 = this.genders.length > 0 ? this.genders[0] : null;
            entityForms.data.status                 = this.statuses.length > 0 ? this.statuses[0] : null;

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        closeModal(force = false) {

            if(this.isSaving && force !== true) return;

            Alerts.modals({type: "hide", id: this.forms[this.entity].createUpdate.extras.modals.default.id});

        },
        handleClose() {

            document.querySelectorAll(".br-quick-create-backdrop").forEach(backdrop => backdrop.remove());

            if(document.querySelector(".modal.show")) {
                document.body.classList.add("modal-open");
            }

        },
        handleShown() {

            const backdrops = document.querySelectorAll(".modal-backdrop");
            const backdrop = backdrops[backdrops.length - 1];

            backdrop?.classList.add("br-quick-create-backdrop");

        },
        async saveEntity() {

            if(this.isSaving) return;

            const entityForms = this.forms[this.entity].createUpdate;

            Alerts.swals({});

            entityForms.errors = {};
            this.isSaving = true;

            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = this.validateFormData(formData);

                if(!validation.bool) {

                    Alerts.generateAlert({messages: Utils.getErrors({errors: validation.errors}), msgContent: this.config.messages.errorValidate});
                    this.isSaving = false;
                    this.handlePostAction({response: validation});
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                const result        = await Requests[requestMethod]({route, data: preparedData, id});

                if(Requests.valid({result})) {

                    this.closeModal(true);
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(entityForms.data, this.MODULE.formFields);
                    this.handlePostAction({response: result});

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: entityForms.errors, config: this.config});
                    this.handlePostAction({response: result});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        validateFormData(formData) {

            const result = Forms.validateFormData(formData, this.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

            return result;

        },
        // Others
        async searchDocumentNumber() {

            const entityForms          = this.forms[this.entity].createUpdate;
            const documentNumber       = entityForms.data.document_number;
            const identityDocumentType = entityForms.data.identity_document_type;

            if(!this.isDefined(documentNumber)) {

                Alerts.generateAlert({msgContent: "Debe ingresar el número de documento para realizar la búsqueda."});
                return;

            }

            if(!this.isDefined(identityDocumentType)) {

                Alerts.generateAlert({msgContent: "Debe seleccionar el tipo de documento."});
                return;

            }

            Alerts.swals({});

            const route    = Requests.config({entity: "helpers", type: "searchDocumentNumber"});
            const formJson = {document_number: documentNumber, type: identityDocumentType.data?.code};
            const response = await Requests.get({route, data: formJson});

            if(Requests.valid({result: response})) {

                const data = response.data.data;

                if(identityDocumentType.data?.code === "dni") {

                    entityForms.data.name = `${data?.first_name || ""} ${data?.last_name || ""} ${data?.second_last_name || ""}`.trim();

                }else if(identityDocumentType.data?.code === "ruc") {

                    entityForms.data.name = data?.legal_name || "";

                }

                Alerts.toastrs({type: "success", subtitle: response?.data?.msg});
                Alerts.swals({show: false});

            }else {

                Alerts.toastrs({type: "error", subtitle: response?.data?.msg});
                Alerts.swals({show: false});

            }

            Alerts.tooltips({show: false});

        },
        isDefined(value) {

            return Utils.isDefined({value});

        },
        tooltips({show = true, time = 10}) {

            Alerts.tooltips({show, time});

        },
        handlePostAction({response}) {

            this.$emit("postAction", {response});

        }
    },
    watch: {
        "forms.customers.createUpdate.data.identity_document_type": {
            handler() {

                const maxLength    = this.documentNumberMaxLength;
                const currentValue = this.forms[this.entity].createUpdate.data.document_number?.toString() || "";

                if(currentValue.length > maxLength) {

                    this.forms[this.entity].createUpdate.data.document_number = currentValue.substring(0, maxLength);

                }

            },
            immediate: false
        }
    }
};
</script>
