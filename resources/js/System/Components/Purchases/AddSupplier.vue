<template>
    <a
        href="javascript:void(0)"
        :class="triggerClasses"
        title="Agregar proveedor"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        @click="openModal">
        <i class="fa-solid fa-circle-plus" aria-hidden="true"></i>
        <span v-if="triggerLabel" v-text="triggerLabel"></span>
    </a>

    <Teleport to="body">
        <div
            :id="modalId"
            class="modal fade fg-modal fg-quick-create-modal"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            tabindex="-1"
            aria-hidden="true"
            role="dialog"
            aria-modal="true"
            @hidden.bs.modal="handleClose"
            @shown.bs.modal="handleShown">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header fg-modal__header">
                        <div>
                            <p class="fg-entity-modal__eyebrow mb-1">Compras</p>
                            <h2 class="modal-title fg-modal__title">Agregar proveedor</h2>
                        </div>
                        <button type="button" class="fg-modal-close" aria-label="Cerrar" @click="closeModal">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="modal-body fg-modal__body fg-quick-create-modal__body">
                        <form @submit.prevent="saveSupplier">
                            <div class="row g-3">
                                <InputSlot
                                    hasDiv
                                    title="Tipo de documento"
                                    :titleClass="[config.forms.classes.title]"
                                    xl="4"
                                    lg="4">
                                    <template #input>
                                        <v-select
                                            v-model="form.documentType"
                                            :options="documentTypes"
                                            :clearable="false"
                                            :searchable="false"/>
                                    </template>
                                </InputSlot>
                                <InputText
                                    v-model="form.documentNumber"
                                    hasDiv
                                    title="Número de documento"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="30"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="errors.document_number"
                                    xl="4"
                                    lg="4"/>
                                <InputText
                                    v-model="form.name"
                                    hasDiv
                                    title="Nombre o razón social"
                                    :titleClass="[config.forms.classes.title]"
                                    isRequired
                                    maxlength="255"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="errors.name"
                                    xl="4"
                                    lg="4"/>
                                <InputText
                                    v-model="form.contactName"
                                    hasDiv
                                    title="Contacto"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="255"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="errors.contact_name"
                                    xl="4"
                                    lg="4"/>
                                <InputText
                                    v-model="form.telephone"
                                    hasDiv
                                    title="Teléfono"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="30"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="errors.telephone"
                                    xl="4"
                                    lg="4"/>
                                <InputText
                                    v-model="form.email"
                                    hasDiv
                                    title="Correo"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="255"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="errors.email"
                                    xl="4"
                                    lg="4"/>
                                <InputText
                                    v-model="form.address"
                                    hasDiv
                                    title="Dirección"
                                    :titleClass="[config.forms.classes.title]"
                                    maxlength="255"
                                    showCharCounter
                                    hasTextBottom
                                    :textBottomInfo="errors.address"
                                    xl="12"
                                    lg="12"/>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer fg-modal__footer">
                        <button type="button" class="fg-button fg-button--secondary" @click="closeModal">Cerrar</button>
                        <button type="button" class="fg-button" :disabled="isSaving" @click="saveSupplier">
                            <span v-text="isSaving ? 'Agregando' : 'Agregar proveedor'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";

const EMPTY_FORM = {
    documentType: {code: "ruc", label: "RUC"},
    documentNumber: "",
    name: "",
    contactName: "",
    telephone: "",
    email: "",
    address: ""
};

export default {
    name: "AddSupplier",
    components: {
        InputSlot,
        InputText
    },
    emits: ["postAction"],
    props: {
        triggerLabel: {
            type: String,
            default: "Agregar"
        }
    },
    data() {
        return {
            modalId: `addSupplierModal-${Math.random().toString(36).slice(2)}`,
            form: {...EMPTY_FORM},
            errors: {},
            isSaving: false,
            config: Constants.generalConfig,
            routes: Requests.config({entity: "suppliers"})
        };
    },
    computed: {
        triggerClasses() {
            return [
                "fg-link",
                "fg-quick-create-trigger",
                "fg-quick-create-trigger--link",
                "ms-1",
                "d-inline-flex",
                "align-items-center"
            ];
        },
        documentTypes() {
            return [
                {code: "ruc", label: "RUC"},
                {code: "dni", label: "DNI"},
                {code: "other", label: "Otro"}
            ];
        }
    },
    methods: {
        openModal() {
            this.form = {...EMPTY_FORM};
            this.errors = {};
            Alerts.modals({type: "show", id: this.modalId});
            Alerts.tooltips({show: true, time: 300});
        },
        closeModal() {
            if(this.isSaving) return;
            Alerts.modals({type: "hide", id: this.modalId});
        },
        handleClose() {
            document.querySelectorAll(".fg-quick-create-backdrop").forEach(backdrop => backdrop.remove());

            if(document.querySelector(".modal.show")) {
                document.body.classList.add("modal-open");
            }
        },
        handleShown() {
            const backdrops = document.querySelectorAll(".modal-backdrop");
            backdrops[backdrops.length - 1]?.classList.add("fg-quick-create-backdrop");
        },
        async saveSupplier() {
            if(this.isSaving) return;

            this.errors = {};

            if(!this.form.name?.trim()) {
                this.errors.name = "Campo obligatorio.";
                return;
            }

            this.isSaving = true;
            Alerts.swals({type: "loading", message: "Agregando proveedor"});

            const result = await Requests.post({
                route: this.routes.store,
                data: {
                    document_type: this.form.documentType?.code || null,
                    document_number: this.form.documentNumber || null,
                    name: this.form.name,
                    contact_name: this.form.contactName || null,
                    telephone: this.form.telephone || null,
                    email: this.form.email || null,
                    address: this.form.address || null,
                    status: "active"
                }
            });

            Alerts.swals({show: false});
            this.isSaving = false;

            if(Requests.valid({result})) {
                this.closeModal();
                Alerts.generateAlert({type: "success", msgContent: result.data?.msg || "Proveedor agregado correctamente."});
                this.$emit("postAction", {response: result});
                return;
            }

            this.errors = result?.errors || result?.data?.errors || {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.errors).flat(),
                msgContent: result?.data?.msg || "Revisa los datos del proveedor."
            });
        }
    }
};
</script>
