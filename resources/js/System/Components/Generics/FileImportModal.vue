<template>
    <FormModal
        :modal-id="modalId"
        :modal-size="modalSize"
        :title="`Importar ${resource}`"
        :close-disabled="busy">
        <template #body>
            <form :id="`${modalId}-form`" class="br-file-import" :aria-busy="busy" @submit.prevent="importRecords">
                <section class="br-file-import__panel br-file-import__panel--template" :aria-labelledby="`${fileId}-template`">
                    <span class="br-file-import__icon" aria-hidden="true"><i class="fa-solid fa-file-excel"></i></span>
                    <div class="br-file-import__summary">
                        <h3 :id="`${fileId}-template`" class="br-file-import__title">Plantilla de {{ resource }}</h3>
                        <p class="br-file-import__help mb-0">{{ instructions }}</p>
                    </div>
                    <button type="button" class="br-btn br-btn-outline-secondary waves-effect" :disabled="busy" @click="downloadTemplate">
                        <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                        {{ downloadingTemplate ? 'Preparando plantilla…' : 'Descargar plantilla Excel' }}
                    </button>
                </section>

                <section class="br-file-import__panel" :aria-labelledby="`${fileId}-upload`">
                    <span class="br-file-import__icon" aria-hidden="true"><i class="fa-solid fa-file-arrow-up"></i></span>
                    <div class="br-file-import__summary">
                        <h3 :id="`${fileId}-upload`" class="br-file-import__title">Archivo de {{ resource }}</h3>
                        <p :id="`${fileId}-help`" class="br-file-import__help mb-0">{{ help }}</p>
                    </div>

                    <div class="br-file-import__details">
                        <slot name="fields" :errors="errors" :busy="busy"/>
                        <div class="br-file-import__picker" :class="{'is-invalid': errors.file}">
                            <input
                                :id="fileId"
                                ref="fileInput"
                                class="visually-hidden br-file-import__input"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                aria-required="true"
                                :aria-invalid="Boolean(errors.file)"
                                :aria-labelledby="`${fileId}-action ${fileId}-upload`"
                                :aria-describedby="errors.file ? `${fileId}-help ${fileId}-error ${fileId}-status` : `${fileId}-help ${fileId}-status`"
                                :disabled="busy"
                                @change="selectFile">
                            <label :id="`${fileId}-action`" :for="fileId" class="br-btn br-btn-outline-secondary" :class="{'is-disabled': busy}">
                                <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                {{ file ? 'Cambiar archivo' : 'Seleccionar archivo' }}
                            </label>
                            <span :id="`${fileId}-status`" class="br-file-import__filename" :class="{'text-muted': !file}" aria-live="polite">
                                {{ file?.name || 'Ningún archivo seleccionado' }}
                            </span>
                        </div>
                        <small v-if="errors.file" :id="`${fileId}-error`" class="br-file-import__help text-danger" role="alert">
                            {{ errors.file }}
                        </small>
                    </div>
                </section>
            </form>
        </template>

        <template #footerClose>
            <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal" :disabled="busy">Cerrar</button>
        </template>

        <template #footer>
            <button type="submit" :form="`${modalId}-form`" class="br-btn br-btn-action-create waves-effect" :disabled="busy">
                <i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i>
                {{ importing ? `Importando ${resource}…` : `Importar ${resource}` }}
            </button>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import FormModal from "@System/Components/Generics/FormModal.vue";

const UNCONFIRMED_IMPORT = "Comprueba el listado antes de volver a importar el archivo.";

function escapeAlertText(value) {
    return String(value).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
}

export default {
    name: "FileImportModal",
    components: {FormModal},
    emits: ["imported"],
    props: {
        modalId: {type: String, required: true},
        modalSize: {type: String, default: "modal-lg"},
        resource: {type: String, required: true},
        instructions: {type: String, required: true},
        importRoute: {type: String, required: true},
        templateFileName: {type: String, required: true},
        help: {type: String, default: "Usa un archivo Excel o CSV de hasta 500 registros."},
        additionalData: {type: Object, default: () => ({})},
        requiredFields: {type: Object, default: () => ({})}
    },
    data() {
        return {file: null, errors: {}, importing: false, downloadingTemplate: false};
    },
    computed: {
        busy() {
            return this.importing || this.downloadingTemplate;
        },
        fileId() {
            return `${this.modalId}-file`;
        }
    },
    mounted() {
        document.getElementById(this.modalId)?.addEventListener("hide.bs.modal", this.preventBusyClose);
    },
    beforeUnmount() {
        document.getElementById(this.modalId)?.removeEventListener("hide.bs.modal", this.preventBusyClose);
    },
    methods: {
        preventBusyClose(event) {
            if(this.busy) event.preventDefault();
        },
        open() {
            if(this.busy) return;
            this.file = null;
            this.errors = {};
            this.$refs.fileInput.value = "";
            Alerts.modals({id: this.modalId});
        },
        selectFile(event) {
            this.file = event.target.files?.[0] ?? null;
            this.clearError("file");
        },
        clearError(field) {
            delete this.errors[field];
        },
        async downloadTemplate() {
            if(this.busy) return;
            this.downloadingTemplate = true;
            Alerts.loading({title: `Preparando la plantilla de ${this.resource}`});

            try {
                await Requests.download({route: this.importRoute, fileName: this.templateFileName, showAlert: true});
            } finally {
                Alerts.close();
                this.downloadingTemplate = false;
            }
        },
        async importRecords() {
            if(this.busy) return;

            this.errors = {};
            if(!this.file) this.errors.file = "Selecciona el archivo que completaste.";
            for(const [field, message] of Object.entries(this.requiredFields)) {
                const value = this.additionalData[field];
                if(value == null || value === "") this.errors[field] = message;
            }
            if(Object.keys(this.errors).length) {
                if(this.errors.file) this.$refs.fileInput.focus();
                return;
            }

            const formData = new FormData();
            formData.append("file", this.file);
            for(const [field, value] of Object.entries(this.additionalData)) formData.append(field, value);

            this.importing = true;
            Alerts.loading({title: `Importando ${this.resource}`});
            let result;
            try {
                result = await Requests.post({route: this.importRoute, formData});
            } catch {
                result = null;
            } finally {
                Alerts.close();
                this.importing = false;
            }

            if(Requests.valid({result})) {
                Alerts.showMessage({type: "success", title: "Importación completada", text: result.data.msg});
                this.$emit("imported", result.data.data);
                return;
            }

            const validation = result?.errors ?? result?.data?.errors ?? {};
            this.errors = Object.fromEntries(Object.entries(validation).map(([field, errors]) => [field, [errors].flat()[0]]));
            const messages = Object.values(validation).flat().filter(Boolean);

            if(messages.length) {
                Alerts.generateAlert({
                    type: "error",
                    headerTitle: "Revisa los datos de la importación",
                    messages: messages.map(escapeAlertText)
                });
                return;
            }

            const knownRejection = result?.code >= 400 && result.code < 500;
            Alerts.showMessage({
                type: "error",
                title: knownRejection ? "No se pudo importar el archivo" : "No pudimos confirmar la importación",
                text: knownRejection ? result?.data?.msg || "Revisa el archivo y vuelve a intentarlo." : UNCONFIRMED_IMPORT
            });
        }
    }
};
</script>
