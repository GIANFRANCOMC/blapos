<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-config-scope-grid">
        <article class="br-config-scope-card">
            <span>Fiscal</span>
            <strong>Identificación legal</strong>
            <p>Documento, razón social, moneda y datos usados para comprobantes y reportes.</p>
        </article>
        <article class="br-config-scope-card">
            <span>Pública</span>
            <strong>Presencia comercial</strong>
            <p>Marca, contacto, redes, logotipo y datos visibles para clientes.</p>
        </article>
        <article class="br-config-scope-card">
            <span>Operativa</span>
            <strong>Reglas internas</strong>
            <p>Parámetros, módulos, integraciones y configuración que impacta la operación.</p>
        </article>
    </section>

    <!-- Content -->
    <div class="nav-align-top" ref="navTabs">
        <ul class="nav nav-tabs nav-fill" role="tablist">
            <li v-for="(tab, index) in tabItems" :key="tab.id" class="nav-item" role="presentation">
                <button type="button" class="nav-link waves-effect justify-content-center" role="tab" data-bs-toggle="tab" :data-bs-target="`#navs-pills-${tab.id}`" :aria-controls="`navs-pills-${tab.id}`" aria-selected="false" tabindex="-1">
                    <span class="d-flex align-items-center fw-semibold" :class="{ 'text-muted': activeTabId !== tab.id }">
                        <i :class="['fa', tab.icon, 'me-2']"></i>
                        <span v-text="`${index + 1}. ${tab.label}`"></span>
                    </span>
                </button>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade" id="navs-pills-general" role="tabpanel">
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
                        <template v-slot:inputGroupAppend>
                            <template v-if="isDocumentTypeSearchable">
                                <button class="br-btn br-btn-search waves-effect" type="button" @click="searchDocumentNumber" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.searchDocumentTooltip">
                                    <span>Buscar</span>
                                </button>
                            </template>
                        </template>
                    </InputText>
                    <InputText
                        v-model="forms[entity].createUpdate.data.legal_name"
                        hasDiv
                        :title="MODULE.texts.form.legalName"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        maxlength="100"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.legal_name"
                        xl="6"
                        lg="6"/>
                    <InputText
                        v-model="forms[entity].createUpdate.data.commercial_name"
                        hasDiv
                        :title="MODULE.texts.form.commercialName"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        maxlength="100"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.commercial_name"
                        xl="6"
                        lg="6"/>
                    <InputText
                        v-model="forms[entity].createUpdate.data.address"
                        hasDiv
                        :title="MODULE.texts.form.address"
                        :titleClass="[config.forms.classes.title]"
                        maxlength="100"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.address"
                        xl="12"
                        lg="12"/>
                    <InputText
                        v-model="forms[entity].createUpdate.data.tagline"
                        hasDiv
                        :title="MODULE.texts.form.tagline"
                        :titleClass="[config.forms.classes.title]"
                        maxlength="200"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.tagline"
                        xl="12"
                        lg="12"/>
                    <InputText
                        v-if="false"
                        v-model="forms[entity].createUpdate.data.description"
                        hasDiv
                        :title="MODULE.texts.form.description"
                        :titleClass="[config.forms.classes.title]"
                        maxlength="200"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.description"
                        xl="12"
                        lg="12"/>
                </div>
            </div>
            <div class="tab-pane fade" id="navs-pills-contacts" role="tabpanel">
                <div class="row g-3">
                    <InputText
                        v-model="forms[entity].createUpdate.data.telephone"
                        hasDiv
                        :title="MODULE.texts.form.telephone"
                        :titleClass="[config.forms.classes.title]"
                        maxlength="15"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.telephone"
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
                        v-model="forms[entity].createUpdate.data.facebook"
                        hasDiv
                        :title="MODULE.texts.form.facebook"
                        :titleClass="[config.forms.classes.title]"
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.facebook"
                        xl="6"
                        lg="6">
                        <template v-slot:inputGroupAppend>
                            <a :href="forms[entity].createUpdate.data.facebook" target="_blank" class="btn btn-info-1 waves-effect" v-if="isValidUrl({url: forms[entity].createUpdate.data.facebook})">
                                <i class="fa fa-globe"></i>
                                <span class="ms-2">Visitar</span>
                            </a>
                        </template>
                    </InputText>
                    <InputText
                        v-model="forms[entity].createUpdate.data.instagram"
                        hasDiv
                        :title="MODULE.texts.form.instagram"
                        :titleClass="[config.forms.classes.title]"
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.instagram"
                        xl="6"
                        lg="6">
                        <template v-slot:inputGroupAppend>
                            <a :href="forms[entity].createUpdate.data.instagram" target="_blank" class="btn btn-info-1 waves-effect" v-if="isValidUrl({url: forms[entity].createUpdate.data.instagram})">
                                <i class="fa fa-globe"></i>
                                <span class="ms-2">Visitar</span>
                            </a>
                        </template>
                    </InputText>
                    <InputText
                        v-model="forms[entity].createUpdate.data.whatsapp"
                        hasDiv
                        :title="MODULE.texts.form.whatsapp"
                        :titleClass="[config.forms.classes.title]"
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.whatsapp"
                        xl="6"
                        lg="6">
                        <template v-slot:inputGroupAppend>
                            <a :href="forms[entity].createUpdate.data.whatsapp" target="_blank" class="btn btn-info-1 waves-effect" v-if="isValidUrl({url: forms[entity].createUpdate.data.whatsapp})">
                                <i class="fa fa-globe"></i>
                                <span class="ms-2">Visitar</span>
                            </a>
                        </template>
                    </InputText>
                </div>
            </div>
            <div class="tab-pane fade" id="navs-pills-branding" role="tabpanel">
                <div class="table-responsive mt-1">
                    <p class="small fw-semibold mb-2">
                        <i class="fa fa-info-circle me-1"></i>
                        <span v-text="fileFormatHintText"></span>
                    </p>
                    <table class="table table-hover">
                        <thead class="align-middle bg-secondary text-center">
                            <tr>
                                <th class="text-white" style="width: 40%;" v-text="MODULE.texts.branding.tablePreview"></th>
                                <th class="text-white" style="width: 60%;" v-text="MODULE.texts.branding.tableUpload"></th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0 bg-white">
                            <tr v-for="item in MODULE.brandingItems" :key="item.formField">
                                <td class="text-center align-middle">
                                    <img v-if="isDefined(forms[entity].createUpdate.data[item.formField])" :src="getAsset(forms[entity].createUpdate.data[item.formField], {type: 'storage'})" width="150" height="150" class="img-fluid object-fit-contain" alt=""/>
                                    <div v-else class="d-flex flex-column align-items-center justify-content-center">
                                        <img :src="getAsset(config.essential.ownerApp.assets.img[item.formField], {type: 'none', back: 1})" width="130" height="130" class="img-fluid object-fit-contain opacity-75" alt=""/>
                                        <div class="badge bg-secondary mt-1" v-text="MODULE.texts.branding.placeholderLabel"></div>
                                    </div>
                                </td>
                                <td class="text-start align-middle">
                                    <InputSlot
                                        :hasDiv="false"
                                        :title="MODULE.texts.form[item.formKey]"
                                        :titleClass="[config.forms.classes.title]">
                                        <template v-slot:input>
                                            <input type="file" class="form-control" :id="item.fileInputId" accept="image/png, image/jpg, image/jpeg"/>
                                        </template>
                                    </InputSlot>
                                    <p class="small text-muted mb-0 mt-1">
                                        <i class="fa fa-lightbulb-o me-1"></i>
                                        <span v-text="item.hint"></span>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="navs-pills-share" role="tabpanel">
                <div class="row g-3">
                    <InputSlot
                        hasDiv
                        :isInputGroup="false"
                        :divInputClass="['d-flex justify-content-center']"
                        xl="6"
                        lg="6">
                        <template v-slot:input>
                            <MyDashboardCompany/>
                        </template>
                    </InputSlot>
                    <InputSlot
                        hasDiv
                        :isInputGroup="false"
                        :divInputClass="['d-flex justify-content-center']"
                        xl="6"
                        lg="6">
                        <template v-slot:input>
                            <MyWebCompany/>
                        </template>
                    </InputSlot>
                </div>
            </div>
            <div class="tab-pane fade" id="navs-pills-configuration" role="tabpanel">
                <div class="row g-3">
                    <InputText
                        v-model="forms[entity].createUpdate.data.slug"
                        hasDiv
                        :title="MODULE.texts.form.slug"
                        :titleClass="[config.forms.classes.title]"
                        isRequired
                        :disabled="true"
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.slug"
                        xl="6"
                        lg="6"/>
                    <InputText
                        v-model="forms[entity].createUpdate.data.token_api_misc"
                        hasDiv
                        :title="MODULE.texts.form.tokenApiMisc"
                        :titleClass="[config.forms.classes.title]"
                        maxlength="100"
                        showCharCounter
                        hasTextBottom
                        :textBottomInfo="forms[entity].createUpdate.errors?.token_api_misc"
                        xl="6"
                        lg="6"/>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3" v-if="hasRecord">
        <div class="row g-3">
            <div class="d-flex flex-row-reverse">
                <button type="button" class="btn waves-effect btn-primary" @click="saveEntity" :disabled="isSaving">
                    <i class="fa fa-save"></i>
                    <span class="ms-2" v-text="MODULE.texts.actions.save"></span>
                </button>
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
    entity: "companies",
    menuId: "menu-configuration-my_company",
    pageTitle: "Mi empresa",
    pageTitleSingular: "Mi empresa",
    breadcrumbParent: "Configuración",
    perPage: 1
};

const FORM_FIELDS = {
    id: null,
    identity_document_type: null,
    document_number: "",
    legal_name: "",
    commercial_name: "",
    address: "",
    tagline: "",
    description: "",
    telephone: "",
    email: "",
    facebook: "",
    instagram: "",
    whatsapp: "",
    logomark: "",
    logotype: "",
    combinationmark: "",
    login_image: "",
    slug: "",
    token_api_misc: "",
    status: null
};

const FORM_FIELD_CONFIG = {
    identity_document_type: {mapToField: "identity_document_type_id"},
    document_number: {trim: true},
    legal_name: {trim: true},
    commercial_name: {trim: true},
    address: {trim: true},
    tagline: {trim: true},
    description: {trim: true},
    telephone: {trim: true, normalize: true},
    email: {trim: true},
    facebook: {trim: true},
    instagram: {trim: true},
    whatsapp: {trim: true},
    status: {getCode: true}
};

const TAB_ITEMS = [
    {id: "general", icon: "fa-building", label: "Información general", visible: true},
    {id: "contacts", icon: "fa-phone", label: "Información de contacto y redes", visible: true},
    {id: "branding", icon: "fa-palette", label: "Identidad visual", visible: true},
    {id: "share", icon: "fa-link", label: "Accesos compartidos", visible: false},
    {id: "configuration", icon: "fa-cog", label: "Parámetros", visible: false}
];

const BRANDING_ITEMS = [
    {formField: "logomark", formKey: "logomark", fileInputId: "logomarkFileId", hint: "Símbolo o ícono de la marca sin texto."},
    {formField: "logotype", formKey: "logotype", fileInputId: "logotypeFileId", hint: "Texto del nombre de la marca, solo letras."},
    {formField: "combinationmark", formKey: "combinationmark", fileInputId: "combinationmarkFileId", hint: "Logo e isotipo combinados en una sola imagen."},
    {formField: "login_image", formKey: "loginImage", fileInputId: "loginImageFileId", hint: "Imagen de la pantalla de inicio de sesión."}
];

const TEXTS = {
    branding: {
        tablePreview: "Imagen",
        tableUpload: "Recurso",
        placeholderLabel: "Plantilla por defecto",
        fileFormatHint: "Formatos validos PNG, JPG o JPEG. Máx. {{size}} MB"
    },
    form: {
        identityDocumentType: "Tipo de documento",
        documentNumber: "Número de documento",
        legalName: "Nombre legal",
        commercialName: "Nombre comercial",
        address: "Dirección",
        tagline: "Slogan",
        description: "Descripción general",
        telephone: "Teléfono",
        email: "Correo electrónico",
        facebook: "Facebook",
        instagram: "Instagram",
        whatsapp: "Whatsapp",
        logomark: "Isotipo",
        logotype: "Logotipo",
        combinationmark: "Marca combinada",
        loginImage: "Imagen de login",
        slug: "Acceso MI WEB",
        tokenApiMisc: "Token API - Misc",
        searchDocumentTooltip: "Buscar N° documento"
    },
    actions: {
        save: "Guardar información"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    texts: TEXTS,
    tabItems: TAB_ITEMS,
    brandingItems: BRANDING_ITEMS
};

export default {
    name: "CompaniesMain",
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        return {
            ...crudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false,
            activeTabId: "general"
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-infrastructure", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        Alerts.swals({type: "initParams"});

        const initParams = await this.initParams();

        this.isInitialized = true;

        const navEl = this.$refs.navTabs;

        if(navEl) navEl.addEventListener("shown.bs.tab", this.onTabShown);

        if(initParams) {

            const initOthers = await this.initOthers();

            if(initOthers) {

                Alerts.swals({show: false});

            }

        }

    },
    beforeUnmount() {

        const navEl = this.$refs.navTabs;

        if(navEl) navEl.removeEventListener("shown.bs.tab", this.onTabShown);

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.company               = response.data.config.company;
                this.options.identityDocumentTypes = response.data.config.identityDocumentTypes;
                this.options.statuses              = response.data.config.statuses;

            }

            return Requests.valid({result: response});

        },
        async initOthers() {

            return new Promise(resolve => {

                const company = (this.options.company?.records ?? []).length > 0 ? this.options.company.records[0] : null;

                if(company) {

                    this.populateFormFromCompany(company);

                }

                this.showFirstTab();
                this.tooltips({show: true, time: 500});

                resolve(true);

            });

        },
        onTabShown(event) {

            const target = event.target?.getAttribute("data-bs-target");

            if(target) {

                this.activeTabId = target.replace("#navs-pills-", "");

            }

        },
        populateFormFromCompany(company) {

            // Map record data to form
            const identityDocumentType = this.identityDocumentTypes.find(e => e.code === company?.identity_document_type_id),
                  status               = this.statuses.find(s => s.code === company?.status),
                  formData             = this.forms[this.entity].createUpdate.data;

            formData.id                     = company?.id;
            formData.identity_document_type = identityDocumentType;
            formData.document_number        = company?.document_number;
            formData.legal_name             = company?.legal_name;
            formData.commercial_name        = company?.commercial_name;
            formData.address                = company?.address;
            formData.tagline                = company?.tagline;
            formData.description            = company?.description;
            formData.telephone              = company?.telephone;
            formData.email                  = company?.email;
            formData.facebook               = company?.facebook;
            formData.instagram              = company?.instagram;
            formData.whatsapp               = company?.whatsapp;
            formData.logomark               = company?.logomark;
            formData.logotype               = company?.logotype;
            formData.combinationmark        = company?.combinationmark;
            formData.login_image            = company?.login_image;
            formData.slug                   = company?.slug;
            formData.token_api_misc         = company?.token_api_misc;
            formData.status                 = status;

        },
        showFirstTab() {

            const tabTrigger = document.querySelector(`[data-bs-target="#navs-pills-${this.activeTabId}"]`);

            if(tabTrigger) {

                const tab = new bootstrap.Tab(tabTrigger);
                tab.show();

            }

        },
        onEmailInput(value) {

            this.forms[this.entity].createUpdate.data.email = (value ?? "").toString().toLowerCase();

        },
        // Forms
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

                    Alerts.generateAlert({
                        messages: Utils.getErrors({errors: validation}),
                        keys: [{column: "section", label: "Sección"}, {column: "msg", label: "Mensaje"}],
                        msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`,
                        width: 800
                    });

                    this.isSaving = false;
                    return;

                }

                const preparedData  = this.prepareFormData(formData, this.MODULE.formFieldConfig);
                const id            = preparedData.get("id");
                const isUpdate      = this.isDefined(id);
                const requestMethod = "patch";
                const route         = this.routeActions["update"];
                const result        = await Requests[requestMethod]({route, formData: preparedData, id});

                if(Requests.valid({result})) {

                    this.updateFormImages(result.data.company);
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    // Forms.clearFormData(entityForms.data, this.MODULE.formFields);
                    this.clearForm();

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: entityForms.errors, config: this.config});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        prepareFormData(formData, formFieldConfig) {

            const preparedData = Forms.prepareFormData(formData, formFieldConfig);

            const excludeFields = ["logomark", "logotype", "combinationmark", "login_image"];

            const fileInputs = [
                { elementId: "logomarkFileId", fieldName: "logomark" },
                { elementId: "logotypeFileId", fieldName: "logotype" },
                { elementId: "combinationmarkFileId", fieldName: "combinationmark" },
                { elementId: "loginImageFileId", fieldName: "login_image" }
            ];

            return Forms.toFormDataWithFiles(preparedData, {excludeFields, fileInputs});

        },
        updateFormImages(company) {

            const formData = this.forms[this.entity].createUpdate.data;

            formData.logomark        = company?.logomark;
            formData.logotype        = company?.logotype;
            formData.combinationmark = company?.combinationmark;
            formData.login_image     = company?.login_image;

        },
        clearForm() {

            const fileInputIds = ["logomarkFileId", "logotypeFileId", "combinationmarkFileId", "loginImageFileId"];

            fileInputIds.forEach(id => {

                const element = document.getElementById(id);

                if(element) element.value = "";

            });

        },
        validateFormData(formData) {

            const result = {
                bool: true,
                identity_document_type_id: [],
                document_number: [],
                legal_name: [],
                commercial_name: [],
                logomark: [],
                logotype: [],
                combinationmark: [],
                login_image: []
            };

            const sections = {
                general: {label: "Información general"},
                contacts: {label: "Información de contacto y redes"},
                branding: {label: "Identidad visual"}
            };

            // Validate required fields
            this.validateRequiredField(result, formData?.identity_document_type, "identity_document_type_id", sections.general.label, "Tipo de documento");
            this.validateRequiredField(result, formData?.document_number, "document_number", sections.general.label, "Número de documento");
            this.validateRequiredField(result, formData?.legal_name, "legal_name", sections.general.label, "Nombre legal");
            this.validateRequiredField(result, formData?.commercial_name, "commercial_name", sections.general.label, "Nombre comercial");

            // Validate image files
            this.validateImageFile(result, "logomarkFileId", "logomark", sections.branding.label, "Isotipo");
            this.validateImageFile(result, "logotypeFileId", "logotype", sections.branding.label, "Logotipo");
            this.validateImageFile(result, "combinationmarkFileId", "combinationmark", sections.branding.label, "Marca combinada");
            this.validateImageFile(result, "loginImageFileId", "login_image", sections.branding.label, "Imagen de login");

            return result;

        },
        validateRequiredField(result, value, fieldName, section, prefix) {

            if(!this.isDefined(value)) {

                result[fieldName].push({section: section, msg: `${prefix}: ${this.config.forms.errors.labels.required}`});

                result.bool = false;

            }

        },
        validateImageFile(result, elementId, fieldName, section, prefix) {

            const fileElement = document.getElementById(elementId);

            if(!fileElement?.files?.length) {

                return;

            }

            const file = fileElement.files[0];
            const allowedExtensions = ["png", "jpg", "jpeg"];
            const fileExtension = file.name.split(".").pop().toLowerCase();
            const maxSize = this.config.forms.inputs.maxSize * 1024;

            if(file.size > maxSize) {

                result[fieldName].push({section: section, msg: `${prefix}: ${this.config.forms.errors.functions.maxSize.numeric(this.config.forms.inputs.maxSize / 1024)}`});

                result.bool = false;

            }else if(!allowedExtensions.includes(fileExtension)) {

                result[fieldName].push({section: section, msg: `${prefix}: ${this.config.forms.errors.labels.not_valid_extension}`});

                result.bool = false;

            }

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

                entityForms.data.legal_name      = data?.legal_name ?? "";
                entityForms.data.commercial_name = data?.commercial_name ?? "";
                entityForms.data.address         = data?.address ?? "";

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
        isValidUrl({url}) {

            return Utils.isValidUrl({url});

        },
        getAsset(path, {type, back}) {

            return Utils.getAsset(path, {type, back});

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
        breadcrumbTitles() {

            return [
                {title: this.MODULE.config.breadcrumbParent},
                this.config.entity.page
            ];

        },
        identityDocumentTypes() {

            return (this.options?.identityDocumentTypes?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        statuses() {

            return (this.options?.statuses ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        hasRecord() {

            return this.isDefined(this.forms[this.entity].createUpdate.data?.id);

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
        maxFileSizeMB() {

            return this.config.forms.inputs.maxSize / 1024;

        },
        fileFormatHintText() {

            return this.MODULE.texts.branding.fileFormatHint.replace("{{size}}", this.maxFileSizeMB);

        },
        tabItems() {

            return MODULE.tabItems.filter(tab => tab.visible);

        }
    },
    watch: {
        "forms.companies.createUpdate.data.identity_document_type": {
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
