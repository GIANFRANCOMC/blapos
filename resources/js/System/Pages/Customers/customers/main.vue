<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Filters -->
    <FiltersSection
        :filter-by-value="filterByValue"
        @update:filterByValue="filterByValue = $event"
        :filter-word-value="filterWordValue"
        @update:filterWordValue="filterWordValue = $event"
        :filter-by-options="filterByOptions"
        :search-placeholder="searchPlaceholder"
        :loading="entityList.extras.loading"
        :filter-by-title="MODULE.texts.filters.filterBy"
        :search-title="MODULE.texts.filters.search"
        :search-button-text="MODULE.texts.actions.search"
        :add-button-text="MODULE.texts.actions.add"
        :show-add-button="true"
        :title-class="[config.forms.classes.title]"
        :select-class="config.forms.classes.select2"
        @search="handleSearch"
        @add="openModal()"/>

    <!-- Records -->
    <div class="list-section mb-1 mb-md-1">
        <Loader v-if="entityList.extras.loading"/>
        <WithoutData v-else-if="entityList.records.total === 0" type="image"/>
        <div v-else-if="entityList.records.total > 0" class="row g-3 g-lg-4">
            <div v-for="record in entityList.records.data" :key="record.id" class="col-12 col-md-6 col-xl-4">
                <div class="card card-list-custom border-0 shadow-sm h-100">
                    <div class="card-header">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="d-flex flex-column flex-grow-1 flex-min-w-0">
                                <span class="text-muted small fw-semibold mb-1" v-text="record.document_number"></span>
                                <span class="text-muted small fst-italic mb-1" v-text="record.identity_document_type?.name"></span>
                                <span class="fs-5 fw-bold text-dark text-truncate" v-text="record.name"></span>
                            </div>
                            <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            <div v-for="field in getCardFields(record)" :key="field.key" class="d-flex align-items-center gap-2">
                                <i :class="[field.icon, 'icon-fixed-width']"></i>
                                <span v-if="field.value" class="text-truncate flex-grow-1 small flex-min-w-0" v-text="field.value"></span>
                                <span v-else class="text-muted small" v-text="field.placeholder"></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 pt-2 border-top">
                            <button type="button" class="btn btn-xs btn-warning waves-effect" @click="openModal(record)">
                                <span v-text="MODULE.texts.actions.edit"></span>
                            </button>
                            <button type="button" class="btn btn-xs btn-success waves-effect" @click="openCarnetModal(record)">
                                <span v-text="MODULE.texts.actions.carnet"></span>
                            </button>
                            <button type="button" class="btn btn-xs btn-info waves-effect" @click="openRegisterBiometricModal(record)">
                                <span v-text="MODULE.texts.actions.registerBiometric"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center">
        <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
    </nav>

    <!-- Modal: Create/Update -->
    <div class="modal fade" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"></h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                        <i class="fa fa-times icon-close-modal"></i>
                    </button>
                </div>
                <div class="modal-body">
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
                                <template v-slot:inputGroupPrepend>
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
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        :class="['btn waves-effect', isUpdate ? 'btn-warning' : 'btn-primary']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <i class="fa fa-save"></i>
                        <span class="ms-2" v-text="MODULE.texts.modal.save"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Carnet -->
    <div class="modal fade" :id="forms[entity].carnet.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="modalTitles.carnet.default"></h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                        <i class="fa fa-times icon-close-modal"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <CarnetCustomer :customer="forms[entity].carnet.data"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Register Biometric Fingerprint -->
    <div class="modal fade" :id="forms[entity].registerBiometric.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="modalTitles.registerBiometric.default"></h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                        <i class="fa fa-times icon-close-modal"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <InputSlot
                            hasDiv
                            :title="MODULE.texts.form.customer"
                            :titleClass="[config.forms.classes.title]"
                            :isInputGroup="false"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <span v-text="forms[entity].registerBiometric.data.customer_name"></span>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            :title="MODULE.texts.form.biometricDevice"
                            :titleClass="[config.forms.classes.title]"
                            isRequired
                            hasTextBottom
                            :textBottomInfo="forms[entity].registerBiometric.errors?.biometric_device_id"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <v-select
                                    v-model="forms[entity].registerBiometric.data.biometric_device"
                                    :options="biometricDevicesOptions"
                                    :class="config.forms.classes.select2"
                                    @close="tooltips({show: true, time: 500})"
                                    :clearable="false"
                                    :searchable="true"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            :title="MODULE.texts.form.deviceUserId"
                            :titleClass="[config.forms.classes.title]"
                            isRequired
                            hasTextBottom
                            :textBottomInfo="forms[entity].registerBiometric.errors?.device_user_id"
                            xl="6"
                            lg="6">
                            <template v-slot:input>
                                <input
                                    v-model="forms[entity].registerBiometric.data.device_user_id"
                                    type="number"
                                    class="form-control"
                                    min="1"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            :title="MODULE.texts.form.fingerIndex"
                            :titleClass="[config.forms.classes.title]"
                            isRequired
                            hasTextBottom
                            :textBottomInfo="forms[entity].registerBiometric.errors?.finger_index"
                            xl="6"
                            lg="6">
                            <template v-slot:input>
                                <v-select
                                    v-model="forms[entity].registerBiometric.data.finger_index"
                                    :options="fingerIndexes"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    :searchable="true"/>
                            </template>
                        </InputSlot>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary waves-effect"
                        @click="registerBiometricFingerprint"
                        :disabled="isSavingBiometric">
                        <i class="fa fa-fingerprint"></i>
                        <span class="ms-2" v-text="MODULE.texts.modal.registerFingerprint"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import { validateOnlyDigits } from "@System/Helpers/ValidationHelpers.js";

const MODULE_CONFIG = {
    entity: "customers",
    menuId: "menu-customers",
    pageTitle: "Clientes",
    pageTitleSingular: "Cliente",
    breadcrumbParent: "Gestión de clientes",
    perPage: 6
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
    identity_document_type: {getCode: true},
    document_number: {trim: true},
    name: {trim: true},
    email: {normalize: true},
    phone_number: {normalize: true},
    gender: {getCode: true},
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

const FORM_FIELDS_REGISTER_BIOMETRIC = {
    biometric_device: null,
    device_user_id: "",
    finger_index: null
};

const FORM_FIELD_CONFIG_REGISTER_BIOMETRIC = {
    biometric_device: {getCode: true},
    device_user_id: {trim: true},
    finger_index: {getCode: true}
};

const VALIDATION_RULES_REGISTER_BIOMETRIC = {
    biometric_device: {required: true},
    device_user_id: {required: true},
    finger_index: {required: true}
};

const ERROR_LABELS_REGISTER_BIOMETRIC = {
    biometric_device: "Dispositivo biométrico",
    device_user_id: "ID de usuario en el dispositivo",
    finger_index: "Índice del dedo"
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "document_number", label: "Número de documento"},
    {code: "name", label: "Nombre"},
    {code: "email", label: "Correo electrónico"},
    {code: "phone_number", label: "Celular"}
];

const TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar",
        edit: "Editar",
        carnet: "Carnet",
        registerBiometric: "Registrar huella"
    },
    card: {
        noEmail: "Sin correo electrónico",
        noPhoneNumber: "Sin celular",
        noBirthdate: "Sin fecha de nacimiento",
        noEmergencyContact: "Sin contacto de emergencia",
        noMedicalNotes: "Sin observaciones médicas",
        noGender: "Sin género"
    },
    form: {
        identityDocumentType: "Tipo de documento",
        documentNumber: "Número de documento",
        name: "Nombre",
        email: "Correo electrónico",
        phoneNumber: "Celular",
        gender: "Género",
        birthdate: "Fecha de nacimiento",
        emergencyBlockTitle: "Contacto de emergencia y salud",
        emergencyBlockSubtitle: "Opcional. Úsalo cuando el equipo necesite actuar rápido ante una incidencia.",
        emergencyContactName: "Contacto de emergencia",
        emergencyContactPhone: "Celular de emergencia",
        medicalNotes: "Observaciones médicas",
        status: "Estado",
        searchDocumentTooltip: "Buscar N° documento",
        customer: "Cliente",
        biometricDevice: "Dispositivo biométrico",
        deviceUserId: "ID de usuario en el dispositivo",
        fingerIndex: "Índice del dedo"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar",
        registerFingerprint: "Registrar huella"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    validationRules: VALIDATION_RULES,
    errorLabels: ERROR_LABELS,
    texts: TEXTS,
    filterOptions: FILTER_OPTIONS
};

export default {
    name: "CustomersMain",
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.lists[MODULE.config.entity].filters.filter_by = MODULE.filterOptions[0];
        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        // Add carnet form
        crudModule.forms[MODULE.config.entity].carnet = {
            extras: {
                modals: {
                    default: {
                        id: Utils.uuid(),
                        titles: {
                            default: "Carnet del cliente"
                        }
                    }
                }
            },
            data: {},
            errors: {}
        };

        // Add registerBiometric form
        crudModule.forms[MODULE.config.entity].registerBiometric = {
            extras: {
                modals: {
                    default: {
                        id: Utils.uuid(),
                        titles: {
                            default: "Registrar huella biométrica"
                        }
                    }
                }
            },
            data: {
                customer_id: null,
                customer_name: "",
                biometric_device: null,
                device_user_id: "",
                finger_index: null
            },
            errors: {}
        };

        return {
            ...crudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false,
            isSavingBiometric: false
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-customers", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams();

        this.isInitialized = true;

        if(initParams) {

            Alerts.swals({show: false});
            this.listEntity({});

        }

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.biometricDevices      = response.data.config.biometricDevices;
                this.options.identityDocumentTypes = response.data.config.identityDocumentTypes;
                this.options.genders               = response.data.config.genders;
                this.options.statuses              = response.data.config.statuses;

            }

            return Requests.valid({result: response});

        },
        // List
        async listEntity(params = null) {

            const entityList   = this.lists[this.entity];
            const emptyRecords = {total: 0, data: [], links: []};
            const filters      = Utils.cloneJson(entityList.filters);
            const filterData   = {per_page: this.MODULE.config.perPage, filter_by: filters.filter_by?.code, word: filters.word};

            entityList.extras.loading = true;

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;

                let requestUrl  = url || entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObj = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(this.isDefined(value) && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);

                    });

                    requestUrl = `${urlObj.pathname}${urlObj.search}`;

                }else {

                    requestData = filterData;

                }

                const response = await Requests.get({route: requestUrl, data: requestData, showAlert: true});

                entityList.records = response?.data ?? emptyRecords;

            }catch(error) {

                entityList.records = emptyRecords;

            }finally {

                entityList.extras.loading = false;

            }

        },
        handleSearch() {

            this.listEntity({});

        },
        // Forms
        openModal(record = null) {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            if(this.isDefined(record)) {

                // Map record data to form
                const identityDocumentTypeOption = this.identityDocumentTypes.find(e => e.code === record?.identity_document_type_id),
                      genderOption               = this.genders.find(g => g.code === record.gender),
                      statusOption               = this.statuses.find(s => s.code === record.status);

                entityForms.data.id                     = record.id;
                entityForms.data.identity_document_type = identityDocumentTypeOption;
                entityForms.data.document_number        = record.document_number;
                entityForms.data.name                   = record.name;
                entityForms.data.email                  = record.email;
                entityForms.data.phone_number           = record.phone_number;
                entityForms.data.gender                 = genderOption;
                entityForms.data.birthdate              = record.birthdate;
                entityForms.data.emergency_contact_name  = record.emergency_contact_name;
                entityForms.data.emergency_contact_phone = record.emergency_contact_phone;
                entityForms.data.medical_notes           = record.medical_notes;
                entityForms.data.status                 = statusOption;

            }else {

                // Set defaults for new record
                entityForms.data.identity_document_type = this.identityDocumentTypes.length > 1 ? this.identityDocumentTypes[1] : null;
                entityForms.data.gender                 = this.genders.length > 0 ? this.genders[0] : null;
                entityForms.data.status                 = this.statuses.length > 0 ? this.statuses[0] : null;

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        async saveEntity() {

            if(this.isSaving) return;

            const entityForms = this.forms[this.entity].createUpdate;

            Alerts.swals({});

            entityForms.errors = {};
            this.isSaving = true;

            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = Forms.validateFormData(formData, this.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

                if(!validation.bool) {

                    Alerts.generateAlert({messages: Utils.getErrors({errors: validation.errors}), msgContent: this.config.messages.errorValidate});
                    this.isSaving = false;
                    return;

                }

                const preparedData = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);

                // Map input data to request data
                preparedData.identity_document_type_id = preparedData.identity_document_type;
                delete preparedData.identity_document_type;

                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                const result        = await Requests[requestMethod]({route, data: preparedData, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: entityForms.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(entityForms.data, this.MODULE.formFields);

                    const entityList  = this.entityList;
                    const currentPage = entityList?.records?.current_page ?? 1;

                    this.listEntity({url: `${entityList?.extras?.route || ""}?page=${currentPage}`});

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: entityForms.errors, config: this.config});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        // Carnet
        openCarnetModal(record = null) {

            const carnetForm = this.forms[this.entity].carnet;

            if(this.isDefined(record)) {

                // Map record data to form
                const identityDocumentTypeOption = this.identityDocumentTypes.find(e => e.code === record?.identity_document_type_id),
                      genderOption               = this.genders.find(g => g.code === record.gender),
                      statusOption               = this.statuses.find(s => s.code === record.status);

                carnetForm.data.id                     = record.id;
                carnetForm.data.identity_document_type = identityDocumentTypeOption;
                carnetForm.data.document_number        = record.document_number;
                carnetForm.data.name                   = record.name;
                carnetForm.data.email                  = record.email;
                carnetForm.data.phone_number           = record.phone_number;
                carnetForm.data.gender                 = genderOption;
                carnetForm.data.birthdate              = record.birthdate;
                carnetForm.data.status                 = statusOption;

            }

            Alerts.modals({type: "show", id: carnetForm.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        // Register Biometric
        openRegisterBiometricModal(record = null) {

            const registerBiometricForm = this.forms[this.entity].registerBiometric;

            registerBiometricForm.errors = {};
            Forms.clearFormData(registerBiometricForm.data, FORM_FIELDS_REGISTER_BIOMETRIC);

            if(this.isDefined(record)) {

                registerBiometricForm.data.customer_id      = record.id;
                registerBiometricForm.data.customer_name    = record.name;
                registerBiometricForm.data.biometric_device = this.biometricDevicesOptions.length > 0 ? this.biometricDevicesOptions[0] : null;
                registerBiometricForm.data.finger_index     = this.fingerIndexes.length > 0 ? this.fingerIndexes[0] : null;

            }else {

                registerBiometricForm.data.customer_id   = null;
                registerBiometricForm.data.customer_name = "";

            }

            Alerts.modals({type: "show", id: registerBiometricForm.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        async registerBiometricFingerprint() {

            if(this.isSavingBiometric) return;

            const registerBiometricForm = this.forms[this.entity].registerBiometric;

            Alerts.swals({});

            registerBiometricForm.errors = {};
            this.isSavingBiometric = true;

            try {

                const formData   = Utils.cloneJson(registerBiometricForm.data);
                const validation = Forms.validateFormData(formData, VALIDATION_RULES_REGISTER_BIOMETRIC, {isDescriptive: true, errorLabels: ERROR_LABELS_REGISTER_BIOMETRIC});

                if(!validation.bool) {

                    Alerts.generateAlert({messages: Utils.getErrors({errors: validation.errors}), msgContent: this.config.messages.errorValidate});
                    this.isSavingBiometric = false;
                    return;

                }

                const preparedData = Forms.prepareFormData(formData, FORM_FIELD_CONFIG_REGISTER_BIOMETRIC);

                // Map input data to request data
                const data = {
                    biometric_device_id: preparedData.biometric_device,
                    device_user_id: preparedData.device_user_id || null,
                    finger_index: preparedData.finger_index || 0
                };

                const route       = Requests.config({entity: "customers", type: "registerBiometricFingerprint"});
                const routeWithId = `${route}/${registerBiometricForm.data.customer_id}`;
                const result      = await Requests.post({route: routeWithId, data});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: registerBiometricForm.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(registerBiometricForm.data, FORM_FIELDS_REGISTER_BIOMETRIC);

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: registerBiometricForm.errors, config: this.config});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSavingBiometric = false;

            }

        },
        // Helpers
        getCardFields(record) {

            return [
                {key: "email", icon: "fa fa-envelope text-primary", value: this.isDefined(record.email) ? record.email : null, placeholder: this.MODULE.texts.card.noEmail},
                {key: "phone_number", icon: "fa fa-phone text-primary", value: this.isDefined(record.phone_number) ? record.phone_number : null, placeholder: this.MODULE.texts.card.noPhoneNumber},
                {key: "emergency_contact", icon: "fa fa-kit-medical text-danger", value: this.getEmergencyContactLabel(record), placeholder: this.MODULE.texts.card.noEmergencyContact},
                {key: "medical_notes", icon: "fa fa-notes-medical text-success", value: this.isDefined(record.medical_notes) ? record.medical_notes : null, placeholder: this.MODULE.texts.card.noMedicalNotes},
                {key: "birthdate", icon: "fa fa-birthday-cake text-warning", value: this.isDefined(record.birthdate) ? this.legibleFormatDate({dateString: record.birthdate, type: "date"}) : null, placeholder: this.MODULE.texts.card.noBirthdate},
                {key: "gender", icon: "fa fa-venus-mars text-info", value: this.isDefined(record.formatted_gender) ? record.formatted_gender : null, placeholder: this.MODULE.texts.card.noGender}
            ];

        },
        getEmergencyContactLabel(record) {

            const name = record.emergency_contact_name;
            const phone = record.emergency_contact_phone;

            if(this.isDefined(name) && this.isDefined(phone)) return `${name} - ${phone}`;
            if(this.isDefined(name)) return name;
            if(this.isDefined(phone)) return phone;

            return null;

        },
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
        // Others
        isDefined(value) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        },
        tooltips({show = true, time = 10}) {

            Alerts.tooltips({show, time});

        }
    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        routeActions() {

            return this.config.entity.routes;

        },
        entityList() {

            return this.lists[this.entity];

        },
        breadcrumbTitles() {

            return [
                {title: this.MODULE.config.breadcrumbParent},
                this.config.entity.page
            ];

        },
        identityDocumentTypes() {

            return (this.options?.identityDocumentTypes?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

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
        modalTitles() {

            return {
                createUpdate: this.forms[this.entity].createUpdate.extras.modals.default.titles,
                carnet: this.forms[this.entity].carnet.extras.modals.default.titles,
                registerBiometric: this.forms[this.entity].registerBiometric.extras.modals.default.titles
            };

        },
        biometricDevicesOptions() {

            return (this.options?.biometricDevices?.records ?? []).map(e => ({
                code: e.id,
                label: `${e.name} (${e.branch?.name ?? ""}) - ${e.ip_address}`,
                data: e
            }));

        },
        fingerIndexes() {

            return [
                {code: 0, label: "Pulgar derecho"},
                {code: 1, label: "Índice derecho"},
                {code: 2, label: "Medio derecho"},
                {code: 3, label: "Anular derecho"},
                {code: 4, label: "Meñique derecho"},
                {code: 5, label: "Pulgar izquierdo"},
                {code: 6, label: "Índice izquierdo"},
                {code: 7, label: "Medio izquierdo"},
                {code: 8, label: "Anular izquierdo"},
                {code: 9, label: "Meñique izquierdo"}
            ];

        },
        filterByOptions() {

            return this.MODULE.filterOptions;

        },
        filterByValue: {
            get() {

                return this.entityList.filters?.filter_by || this.MODULE.filterOptions[0];

            },
            set(value) {

                this.entityList.filters.filter_by = value;

            }
        },
        filterWordValue: {
            get() {

                return this.entityList.filters.word || "";

            },
            set(value) {

                this.entityList.filters.word = value;

            }
        },
        searchPlaceholder() {

            const filterBy = this.entityList.filters.filter_by;

            if(!filterBy) return "Buscar...";

            return `Buscar por ${(filterBy.label || "...").toLowerCase()}`;

        },
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
    watch: {
        "forms.customers.createUpdate.data.identity_document_type": {
            handler(newValue) {

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

<style scoped>
</style>
