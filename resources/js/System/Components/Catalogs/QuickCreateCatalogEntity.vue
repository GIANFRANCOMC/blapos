<template>
    <QuickCreateTrigger
        :mode="triggerMode"
        :text="triggerText"
        :title="resolvedTriggerTitle"
        :aria-label="resolvedTriggerTitle"
        :show-tooltip="triggerTooltip"
        :icon="triggerIcon"
        :custom-class="triggerClass"
        :disabled="disabled"
        @click="openModal"/>

    <Teleport to="body">
        <div
            ref="modal"
            :id="resolvedModalId"
            class="modal fade br-entity-modal br-quick-create-modal"
            data-bs-backdrop="static"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="`${resolvedModalId}-title`"
            @keydown.esc.prevent="closeModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header br-entity-modal__header">
                        <div>
                            <p class="br-entity-modal__eyebrow mb-1" v-text="eyebrow"></p>
                            <h2
                                :id="`${resolvedModalId}-title`"
                                class="modal-title br-entity-modal__title"
                                v-text="`Agregar ${singularLabel.toLowerCase()}`">
                            </h2>
                        </div>

                        <button
                            type="button"
                            class="br-modal-close"
                            aria-label="Cerrar"
                            @click="closeModal">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>

                    <form
                        class="modal-body br-entity-modal__body br-quick-create-modal__body"
                        @submit.prevent="saveEntity">
                        <div v-if="generalError" class="br-quick-create-dialog__alert" role="alert">
                            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                            <span v-text="generalError"></span>
                        </div>

                        <div class="row g-3">
                            <InputText
                                ref="nameInput"
                                v-model="form.name"
                                hasDiv
                                title="Nombre"
                                :titleClass="labelClasses"
                                isRequired
                                :maxlength="nameMaxlength"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="errors.name"
                                autocomplete="off"
                                xl="12"
                                lg="12"
                                @enterKeyPressed="saveEntity"/>

                            <InputText
                                v-model="form.description"
                                hasDiv
                                title="Descripción"
                                :titleClass="labelClasses"
                                :maxlength="descriptionMaxlength"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="errors.description"
                                autocomplete="off"
                                xl="12"
                                lg="12"
                                @enterKeyPressed="saveEntity"/>
                        </div>

                        <p class="br-quick-create-dialog__note">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            <span>
                                Se generará automáticamente un código interno asociado y se registrará con estado activo.
                            </span>
                        </p>
                    </form>

                    <div class="modal-footer br-entity-modal__footer">
                        <button type="button" class="br-btn br-btn-cancel" @click="closeModal">
                            Cancelar
                        </button>
                        <button
                            type="button"
                            class="br-btn br-btn-action-create"
                            :disabled="isSaving"
                            @click="saveEntity">
                            <span v-text="isSaving ? 'Agregando' : `Agregar ${singularLabel.toLowerCase()}`"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import QuickCreateTrigger from "@System/Components/Generics/QuickCreateTrigger.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

let quickCreateSequence = 0;

const FORM_DEFAULTS = {
    internal_code: "",
    name: "",
    description: "",
    status: "active"
};

export default {
    name: "QuickCreateCatalogEntity",
    components: {
        QuickCreateTrigger
    },
    emits: ["created", "postAction"],
    props: {
        entity: {
            type: String,
            required: true
        },
        resourceKey: {
            type: String,
            required: true
        },
        singularLabel: {
            type: String,
            required: true
        },
        eyebrow: {
            type: String,
            default: "Catálogo e inventario"
        },
        triggerMode: {
            type: String,
            default: "link"
        },
        triggerText: {
            type: String,
            default: "Agregar"
        },
        triggerTitle: {
            type: String,
            default: ""
        },
        triggerTooltip: {
            type: Boolean,
            default: true
        },
        triggerIcon: {
            type: String,
            default: "fa-solid fa-circle-plus"
        },
        triggerClass: {
            type: [String, Array, Object],
            default: ""
        },
        dialogId: {
            type: String,
            default: ""
        },
        nameMaxlength: {
            type: Number,
            default: 100
        },
        internalCodeMaxlength: {
            type: Number,
            default: 50
        },
        descriptionMaxlength: {
            type: Number,
            default: 250
        },
        internalCodePattern: {
            type: String,
            default: ""
        },
        internalCodePatternMessage: {
            type: String,
            default: "El código interno contiene caracteres no permitidos."
        },
        internalCodePrefix: {
            type: String,
            default: ""
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data() {

        quickCreateSequence += 1;

        return {
            instanceId: quickCreateSequence,
            form: Utils.cloneJson(FORM_DEFAULTS),
            errors: {},
            generalError: "",
            isSaving: false
        };

    },
    computed: {
        resolvedModalId() {

            return this.dialogId || `br-quick-create-${this.entity}-${this.instanceId}`;

        },
        resolvedTriggerTitle() {

            return this.triggerTitle || `Agregar ${this.singularLabel.toLowerCase()}`;

        },
        labelClasses() {

            return ["form-label", "colon-at-end"];

        },
        errorLabels() {

            return {
                internal_code: "Código interno",
                name: "Nombre",
                description: "Descripción"
            };

        },
        validationRules() {

            return {
                internal_code: {
                    required: true,
                    maxLength: this.internalCodeMaxlength,
                    custom: value => {

                        if(!this.internalCodePattern || !Utils.isDefined({value})) return "";

                        return new RegExp(this.internalCodePattern).test(String(value))
                            ? ""
                            : this.internalCodePatternMessage;

                    }
                },
                name: {
                    required: true,
                    maxLength: this.nameMaxlength
                },
                description: {
                    required: false,
                    maxLength: this.descriptionMaxlength
                }
            };

        }
    },
    mounted() {

        this.$refs.modal?.addEventListener("hidden.bs.modal", this.handleClose);
        this.$refs.modal?.addEventListener("shown.bs.modal", this.handleShown);

    },
    beforeUnmount() {

        this.$refs.modal?.removeEventListener("hidden.bs.modal", this.handleClose);
        this.$refs.modal?.removeEventListener("shown.bs.modal", this.handleShown);
        Alerts.modals({type: "hide", id: this.resolvedModalId});

    },
    methods: {
        openModal() {

            if(this.disabled || this.isSaving) return;

            this.resetForm();
            Alerts.modals({type: "show", id: this.resolvedModalId});

        },
        closeModal() {

            if(this.isSaving) return;

            Alerts.modals({type: "hide", id: this.resolvedModalId});

        },
        handleClose() {

            this.errors = {};
            this.generalError = "";
            document.querySelectorAll(".br-quick-create-backdrop").forEach(backdrop => backdrop.remove());

            if(document.querySelector(".modal.show")) {
                document.body.classList.add("modal-open");
            }

        },
        handleShown() {

            const backdrops = document.querySelectorAll(".modal-backdrop");
            const backdrop = backdrops[backdrops.length - 1];

            backdrop?.classList.add("br-quick-create-backdrop");
            this.focusNameInput();

        },
        focusNameInput() {

            this.$nextTick(() => this.$refs.modal?.querySelector("input")?.focus());

        },
        resetForm() {

            this.form = {
                ...Utils.cloneJson(FORM_DEFAULTS),
                internal_code: this.buildInternalCode()
            };
            this.errors = {};
            this.generalError = "";

        },
        buildInternalCode() {

            const code = Utils.generateCode({length: 7});
            const prefix = String(this.internalCodePrefix ?? "").trim().toUpperCase();

            return prefix ? `${prefix}-${code}` : code;

        },
        showValidationSummary(errors) {

            Alerts.generateAlert({
                type: "error",
                messages: Forms.getDescriptiveErrors(errors, this.errorLabels),
                msgContent: "Revise los campos indicados para continuar.",
                width: 430
            });

        },
        async saveEntity() {

            if(this.isSaving) return;

            this.errors = {};
            this.generalError = "";

            const validation = Forms.validateFormData(
                this.form,
                this.validationRules,
                {
                    isDescriptive: false,
                    errorLabels: this.errorLabels
                }
            );

            if(!validation.bool) {

                this.errors = validation.errors;
                this.showValidationSummary(validation.errors);
                return;

            }

            this.isSaving = true;
            Alerts.swals({
                type: "create",
                entity: this.singularLabel.toLowerCase()
            });

            try {

                const data = Forms.prepareFormData(
                    Utils.cloneJson(this.form),
                    {
                        internal_code: {trim: true},
                        name: {trim: true},
                        description: {normalize: true},
                        status: {trim: true}
                    }
                );
                const result = await Requests.post({
                    route: Requests.config({entity: this.entity, type: "store"}),
                    data
                });

                Alerts.swals({show: false});

                if(Requests.valid({result})) {

                    const record = result.data?.[this.resourceKey] ?? null;

                    this.$emit("created", {record, response: result});
                    this.$emit("postAction", {response: result});
                    Alerts.modals({type: "hide", id: this.resolvedModalId});

                    Alerts.generateAlert({
                        type: "success",
                        headerTitle: `${this.singularLabel} agregada`,
                        msgContent: `${this.singularLabel} se registró con estado activo y ya está disponible para seleccionarla.`,
                        width: 430
                    });

                }else {

                    Forms.handleFormResponseErrors({
                        result,
                        formErrorsObject: this.errors,
                        errorLabels: this.errorLabels,
                        showAlert: true
                    });

                    if(!Object.keys(this.errors).length) {
                        this.generalError = result?.data?.msg || "No fue posible completar el registro.";
                    }

                    this.$emit("postAction", {response: result});

                }

            }catch(error) {

                Alerts.swals({show: false});
                this.generalError = error?.message || "No fue posible completar el registro.";

            }finally {

                this.isSaving = false;

            }

        }
    }
};
</script>
