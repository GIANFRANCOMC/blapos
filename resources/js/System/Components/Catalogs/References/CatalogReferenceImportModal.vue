<template>
    <FileImportModal
        ref="importModal"
        :modal-id="modalId"
        :resource="resource"
        :instructions="`${intro} ${internalCodeImportHelp(internalCodePrefix)}`"
        :help="help"
        :import-route="importRoute"
        :template-file-name="templateFileName"
        @imported="$emit('imported', $event)"/>
</template>

<script>
import {internalCodeImportHelp} from "@System/Helpers/InternalCodes.js";
import FileImportModal from "@System/Components/Generics/FileImportModal.vue";

export default {
    name: "CatalogReferenceImportModal",
    components: {FileImportModal},
    emits: ["imported"],
    props: {
        internalCodePrefix: {type: String, default: ""},
        modalId: {type: String, required: true},
        resource: {type: String, required: true},
        templateFileName: {type: String, required: true},
        importRoute: {type: String, required: true},
        intro: {type: String, default: "Completa el nombre de cada registro en la plantilla."},
        help: {type: String, default: "Usa un archivo Excel o CSV de hasta 500 registros."}
    },
    methods: {
        internalCodeImportHelp,
        open() {
            this.$refs.importModal.open();
        }
    }
};
</script>
