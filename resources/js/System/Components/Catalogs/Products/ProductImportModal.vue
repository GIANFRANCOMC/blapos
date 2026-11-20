<template>
    <FileImportModal
        ref="importModal"
        :modal-id="modalId"
        resource="productos"
        :instructions="instructions"
        :import-route="importRoute"
        template-file-name="plantilla_productos.xlsx"
        :additional-data="{warehouse_id: warehouse?.code}"
        :required-fields="{warehouse_id: 'Selecciona el almacén para el stock inicial.'}"
        @imported="$emit('imported', $event)">
        <template #fields="{errors, busy}">
            <FgField  label="Almacén para el stock inicial" required :input-id="modalId + '-warehouse'" :error="errors.warehouse_id">
                <FgSelect :input-id="modalId + '-warehouse'" v-model="warehouse" :options="warehouseOptions" class="fg-select" :clearable="false" :searchable="false" :disabled="busy" @update:model-value="$refs.importModal.clearError('warehouse_id')"/>
            </FgField>

        </template>
    </FileImportModal>
</template>

<script>
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import {internalCodeImportHelp} from "@System/Helpers/InternalCodes.js";
import FileImportModal from "@System/Components/Generics/FileImportModal.vue";
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";

export default {
    name: "ProductImportModal",
    components: {FgSelect,FgField,FileImportModal, SelectNoOptions},
    emits: ["imported"],
    props: {
        internalCodePrefix: {type: String, default: ""},
        modalId: {type: String, default: "productImportModal"},
        importRoute: {type: String, required: true},
        warehouses: {type: Array, default: () => []}
    },
    data() {
        return {warehouse: null};
    },
    computed: {
        instructions() {
            return `Completa el nombre y el precio de cada producto. ${internalCodeImportHelp(this.internalCodePrefix)} El código de barras se genera si lo dejas vacío.`;
        },
        warehouseOptions() {
            return this.warehouses.map(warehouse => ({
                code: warehouse.id,
                label: `${warehouse.branch?.name ? `${warehouse.branch.name} - ` : ""}${warehouse.name}`
            }));
        }
    },
    methods: {
        open() {
            if(this.$refs.importModal.busy) return;
            this.warehouse = this.warehouseOptions[0] ?? null;
            this.$refs.importModal.open();
        }
    }
};
</script>
