<template>
    <FormModal :modal-id="modalId" title="CARGAR MASIVAMENTE" modal-size="modal-lg">
        <template #body>
            <p class="br-import-products__intro">
                Descarga la plantilla y completa el nombre de cada registro. El código interno vacío se generará automáticamente.
            </p>

            <button
                type="button"
                class="br-import-products__template"
                :disabled="downloadingTemplate || importing"
                @click="downloadTemplate">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                <span>Descargar plantilla</span>
            </button>

            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label :for="`${modalId}-file`" class="form-label colon-at-end">
                        Archivo de {{ resource }}<span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input
                        :id="`${modalId}-file`"
                        ref="fileInput"
                        class="form-control"
                        type="file"
                        aria-required="true"
                        accept=".xlsx,.xls,.csv"
                        :disabled="importing"
                        @change="selectFile">
                    <small class="br-import-products__help">Se aceptan archivos Excel o CSV de hasta 500 registros.</small>
                    <small v-if="errors.file" class="text-danger d-block" v-text="firstError(errors.file)"></small>
                </div>
            </div>
        </template>

        <template #footerClose>
            <button ref="closeButton" type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal" :disabled="importing">
                Cancelar
            </button>
        </template>

        <template #footer>
            <button type="button" class="br-btn br-btn-action-create" :disabled="importing || downloadingTemplate" @click="importRecords">
                Cargar
            </button>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import FormModal from "@System/Components/Generics/FormModal.vue";

export default {
    name: "CatalogReferenceImportModal",
    components: {FormModal},
    emits: ["imported"],
    props: {
        modalId: {type: String, required: true},
        resource: {type: String, required: true},
        templateFileName: {type: String, required: true},
        importRoute: {type: String, required: true}
    },
    data() {

        return {
            importing: false,
            downloadingTemplate: false,
            file: null,
            errors: {}
        };

    },
    methods: {
        open() {

            this.file = null;
            this.errors = {};

            if(this.$refs.fileInput) {

                this.$refs.fileInput.value = "";

            }

            Alerts.modals({type: "show", id: this.modalId});

        },
        selectFile(event) {

            this.file = event.target.files?.[0] ?? null;
            delete this.errors.file;

        },
        async downloadTemplate() {

            if(this.downloadingTemplate || this.importing) {

                return;

            }

            this.downloadingTemplate = true;
            Alerts.swals({type: "loading", message: "Preparando plantilla"});

            try {

                await Requests.download({
                    route: this.importRoute,
                    fileName: this.templateFileName,
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});
                this.downloadingTemplate = false;

            }

        },
        async importRecords() {

            if(this.importing || this.downloadingTemplate) {

                return;

            }

            this.errors = {};

            if(!this.file) {

                this.errors.file = ["Selecciona el archivo completado."];
                return;

            }

            this.importing = true;
            Alerts.swals({type: "loading", message: `Importando ${this.resource}`});

            try {

                const formData = new FormData();
                formData.append("file", this.file);

                const result = await Requests.post({route: this.importRoute, formData});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: this.modalId});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                    this.$emit("imported", result.data.data);
                    return;

                }

                this.errors = result?.errors ?? result?.data?.errors ?? {};
                const messages = Object.values(this.errors).flat();

                Alerts.generateAlert({
                    type: "error",
                    messages: messages.length ? messages : [result?.data?.msg || "No se pudo completar la carga masiva."]
                });

            }finally {

                Alerts.swals({show: false});
                this.importing = false;

            }

        },
        firstError(error) {

            return Array.isArray(error) ? error[0] : error;

        }
    }
};
</script>
