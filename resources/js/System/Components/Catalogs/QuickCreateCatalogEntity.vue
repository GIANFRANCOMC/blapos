<template>

<button ref="trigger" type="button" class="fg-link" :disabled="disabled || isSaving" :aria-label="resolvedTriggerTitle" @click="openModal">Agregar</button>
    <Teleport to="body"><div ref="modal" :id="resolvedModalId" class="modal fade fg-modal fg-modal--compact" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-modal="true" :aria-labelledby="resolvedModalId + '-title'" @keydown.esc.prevent="closeModal">
        <div class="modal-dialog modal-dialog-centered" role="document"><div class="fg-modal__content">
            <header class="fg-modal__header"><h2 :id="resolvedModalId + '-title'" class="fg-modal__title">Agregar {{ singularLabel.toLowerCase() }}</h2><button type="button" class="fg-button fg-button--icon" :disabled="isSaving" aria-label="Cerrar" @click="closeModal"><i class="fa-solid fa-xmark" aria-hidden="true"/></button></header>
            <form :id="resolvedModalId + '-form'" class="fg-modal__body fg-stack" @submit.prevent="saveEntity">
                <div v-if="generalError" class="fg-notice fg-notice--danger" role="alert">{{ generalError }}</div>
                <InputText ref="nameInput" v-model="form.name" title="Nombre" is-required :maxlength="nameMaxlength" show-char-counter has-text-bottom :text-bottom-info="errors.name" :disabled="isSaving" autocomplete="off"/>
                <InputTextArea v-model="form.description" title="Descripción" :maxlength="descriptionMaxlength" placeholder="Opcional" has-text-bottom :text-bottom-info="errors.description" :disabled="isSaving"/>
                <small v-if="errors.internal_code" class="fg-error" role="alert">{{ Array.isArray(errors.internal_code) ? errors.internal_code[0] : errors.internal_code }}</small>
            </form>
            <footer class="fg-modal__footer"><button type="button" class="fg-button fg-button--secondary" :disabled="isSaving" @click="closeModal">Cerrar</button><button type="submit" :form="resolvedModalId + '-form'" class="fg-button" :disabled="isSaving">{{ isSaving ? 'Guardando…' : 'Guardar ' + singularLabel.toLowerCase() }}</button></footer>
        </div></div>
    </div></Teleport>


</template>
<script>
import InputText from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InternalCodePrefixMixin from "@System/Mixins/InternalCodePrefixMixin.js";

let quickCreateSequence = 0;

const FORM_DEFAULTS = {
    internal_code: "",
    name: "",
    description: "",
    status: "active"
};

export default {
    name: "QuickCreateCatalogEntity",
    components: {InputText, InputTextArea,
    },
    mixins: [InternalCodePrefixMixin],
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
        internalCodePrefix: {type: String, default: ""},
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

            return this.dialogId || `fg-quick-create-${this.entity}-${this.instanceId}`;

        },
        resolvedTriggerTitle() {

            return this.triggerTitle || `Agregar ${this.singularLabel.toLowerCase()}`;

        },
        labelClasses() {

            return ["form-label", "colon-at-end"];

        },
        internalCodePrefixes() {
            return {[this.resourceKey]: this.internalCodePrefix};
        },
        internalCodeEntityKey() {
            return this.resourceKey;
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
            const parent = document.querySelector(".modal.show");
            if(parent && parent !== this.$refs.modal) {
                Alerts.showStackedModal({id: this.resolvedModalId, parent, onHidden: () => this.$refs.trigger?.focus()});
            } else {
                Alerts.modals({type: "show", id: this.resolvedModalId});
            }

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
                internal_code: this.setEditableInternalCode(Utils.generateCode({length: 7}), true)
            };
            this.errors = {};
            this.generalError = "";

        },
        showValidationSummary(errors) {

            Alerts.generateAlert({
                type: "error",
                headerTitle: "Revisa los campos indicados",
                messages: Forms.getDescriptiveErrors(errors, this.errorLabels),
                width: 430
            });

        },
        async saveEntity() {

            if(this.isSaving) return;

            this.errors = {};
            this.generalError = "";
            const payload = {...this.form, ...this.internalCodePayload(this.form.internal_code)};

            const validation = Forms.validateFormData(
                payload,
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
                    payload,
                    {
                        internal_code: {trim: false},
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

                    Alerts.showMessage({
                        type: "success",
                        title: `${this.singularLabel} guardada`,
                        text: "Ya está disponible para seleccionarla en el formulario.",
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
