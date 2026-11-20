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
            <div class="br-file-import__field">
                <label :for="`${modalId}-warehouse`" class="form-label colon-at-end required">Almacén para el stock inicial</label>
                <v-select
                    v-model="warehouse"
                    :input-id="`${modalId}-warehouse`"
                    :options="warehouseOptions"
                    :clearable="false"
                    :searchable="false"
                    :disabled="busy"
                    append-to-body
                    @update:model-value="$refs.importModal.clearError('warehouse_id')">
                    <template #search="{attributes, events}">
                        <input
                            class="vs__search"
                            v-bind="attributes"
                            v-on="events"
                            aria-required="true"
                            :aria-invalid="Boolean(errors.warehouse_id)"
                            :aria-describedby="errors.warehouse_id ? `${modalId}-warehouse-error` : undefined">
                    </template>
                    <template #no-options>
                        <SelectNoOptions/>
                    </template>
                </v-select>
                <small v-if="errors.warehouse_id" :id="`${modalId}-warehouse-error`" class="br-file-import__help text-danger" role="alert">
                    {{ errors.warehouse_id }}
                </small>
            </div>
        </template>
    </FileImportModal>
</template>

<script>
import {internalCodeImportHelp} from "@System/Helpers/InternalCodes.js";
import FileImportModal from "@System/Components/Generics/FileImportModal.vue";
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";

export default {
    name: "ProductImportModal",
    components: {FileImportModal, SelectNoOptions},
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
