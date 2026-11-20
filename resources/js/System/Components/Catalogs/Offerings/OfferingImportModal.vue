<template>
    <FileImportModal
        ref="importModal"
        :modal-id="`${type}ImportModal`"
        modal-size=""
        :resource="resource"
        :instructions="instructions"
        :import-route="importRoute"
        :template-file-name="templateFileName"
        @imported="$emit('imported', $event)"/>
</template>

<script>
import {internalCodeImportHelp} from "@System/Helpers/InternalCodes.js";
import FileImportModal from "@System/Components/Generics/FileImportModal.vue";

export default {
    name: "OfferingImportModal",
    components: {FileImportModal},
    emits: ["imported"],
    props: {
        internalCodePrefix: {type: String, default: ""},
        type: {type: String, required: true, validator: value => ["service", "subscription"].includes(value)},
        importRoute: {type: String, required: true}
    },
    computed: {
        resource() {
            return this.type === "service" ? "servicios" : "membresías";
        },
        templateFileName() {
            return this.type === "service" ? "plantilla_servicios.xlsx" : "plantilla_membresias.xlsx";
        },
        instructions() {
            const fields = this.type === "service"
                ? "Completa el nombre y el precio. La duración estimada es opcional."
                : "Completa el nombre, el precio y la duración (hora, día, rutina, mes o año).";

            return `${fields} ${internalCodeImportHelp(this.internalCodePrefix)}`;
        }
    },
    methods: {
        open() {
            this.$refs.importModal.open();
        }
    }
};
</script>
