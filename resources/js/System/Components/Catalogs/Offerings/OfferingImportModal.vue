<template>
    <FormModal
        :modal-id="modalId"
        title="Cargar masivamente"
        modal-class="br-catalog-import-modal"
        :keyboard="false"
        :close-disabled="importing">
        <template #body>
            <p class="br-import-products__intro" v-text="introText"></p>

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
                    <label :for="`${modalId}-file`" class="form-label colon-at-end required">{{ fileLabel }}</label>
                    <input
                        :id="`${modalId}-file`"
                        ref="fileInput"
                        class="form-control"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        @change="selectFile">
                    <small class="br-import-products__help">Se aceptan archivos Excel o CSV. Máximo 500 registros; el tamaño permitido se valida al cargar.</small>
                    <small v-if="errors.file" class="text-danger d-block" v-text="firstError(errors.file)"></small>
                </div>
            </div>
        </template>

        <template #footerClose>
            <button ref="closeButton" type="button" class="br-btn br-btn-cancel" :disabled="importing" data-bs-dismiss="modal">
                Cancelar
            </button>
        </template>

        <template #footer>
            <button type="button" class="br-btn br-btn-action-create" :disabled="importing || downloadingTemplate" @click="importRecords">
                {{ importing ? "Cargando..." : "Cargar" }}
            </button>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import FormModal from "@System/Components/Generics/FormModal.vue";

export default {
    name: "OfferingImportModal",
    components: {FormModal},
    emits: ["imported"],
    props: {
        type: {type: String, required: true, validator: value => ["service", "subscription"].includes(value)},
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
    computed: {
        modalId() {

            return `${this.type}ImportModal`;

        },
        fileLabel() {

            return this.type === "service" ? "Archivo de servicios" : "Archivo de membresías";

        },
        fileName() {

            return this.type === "service" ? "plantilla_servicios.xlsx" : "plantilla_membresias.xlsx";

        },
        introText() {

            return this.type === "service"
                ? "Completa nombre y precio; la duración estimada es opcional. Los códigos vacíos se generan automáticamente."
                : "Completa nombre, precio, valor y tipo de duración (hora, día, rutina, mes o año). Los códigos vacíos se generan automáticamente.";

        }
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

                await Requests.download({route: this.importRoute, fileName: this.fileName, showAlert: true});

            }finally {

                Alerts.swals({show: false});
                this.downloadingTemplate = false;

            }

        },
        async importRecords() {

            this.errors = {};

            if(!this.file) {

                this.errors.file = ["Selecciona el archivo completado."];

                return;

            }

            this.importing = true;
            Alerts.swals({type: "loading", message: "Cargando registros"});

            const formData = new FormData();
            formData.append("file", this.file);

            let result = null;

            try {

                result = await Requests.post({route: this.importRoute, formData});

            }catch(error) {

                result = null;

            }finally {

                Alerts.swals({show: false});
                this.importing = false;

            }

            if(Requests.valid({result})) {

                this.$refs.closeButton?.click();
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

        },
        firstError(error) {

            return Array.isArray(error) ? error[0] : error;

        }
    }
};
</script>
