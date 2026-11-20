<template>
    <div  class="fg-table-scroll">
        <table class="fg-table fg-stock-table">
            <thead><tr><th scope="col">Sucursal y almacén</th><th scope="col" class="fg-align-end">{{ currentLabel }}</th><th scope="col" class="fg-align-end">Stock mínimo</th><th scope="col">Estado</th></tr></thead>
            <tbody><tr v-for="item in displayRows" :key="item.inventory.warehouse_id">
                <td><div class="fg-stock-location"><strong>{{ item.inventory.branch_name }}</strong><small>{{ item.inventory.warehouse_name }}</small></div></td>
                <td class="fg-align-end"><InputNumber v-if="mode === 'edit' && !isUpdate" :model-value="item.inventory.initial_stock" :title="currentLabel" :aria-label="currentLabel + ' · ' + item.inventory.branch_name + ' · ' + item.inventory.warehouse_name" :min-value="0" :disabled="disabled" has-text-bottom :text-bottom-info="fieldErrors(item.index, 'initial_stock')" @update:modelValue="updateRow(item.index, 'initial_stock', $event)"/><QuantityValue v-else :value="item.inventory.initial_stock" :unit="measurementUnit"/></td>
                <td class="fg-align-end"><InputNumber v-if="mode === 'edit'" :model-value="item.inventory.minimum_stock" title="Stock mínimo" :aria-label="'Stock mínimo · ' + item.inventory.branch_name + ' · ' + item.inventory.warehouse_name" :min-value="0" :disabled="disabled" has-text-bottom :text-bottom-info="fieldErrors(item.index, 'minimum_stock')" @update:modelValue="updateRow(item.index, 'minimum_stock', $event)"/><QuantityValue v-else :value="item.inventory.minimum_stock" :unit="measurementUnit"/></td>
                <td><span v-if="item.status" :class="['fg-badge', item.status.code === 'healthy' ? 'fg-badge--success' : item.status.code === 'low' ? 'fg-badge--warning' : 'fg-badge--danger']">{{ item.status.code === 'healthy' ? 'Disponible' : item.status.text }}</span><span v-else class="fg-help">—</span></td>
            </tr></tbody>
        </table>
    </div>

</template>

<script>
import InputNumber from "@System/Components/InputNumber.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import {inventoryStockStatus} from "./productInventory.js";

export default {
    name: "ProductWarehouseInventory",
    components: {InputNumber, QuantityValue},
    emits: ["update:rows"],
    props: {
        disabled: Boolean,
        rows: {type: Array, required: true},
        mode: {type: String, default: "view"},
        isUpdate: {type: Boolean, default: false},
        removeDisabled: {type: Boolean, default: false},
        measurementUnit: {type: Object, default: null},
        errors: {type: Object, default: () => ({})}
    },
    computed: {
        currentLabel() {

            return this.mode === "edit" && !this.isUpdate ? "Stock inicial" : "Stock actual";

        },
        displayRows() {

            return this.rows.map((inventory, index) => ({
                inventory,
                index,
                status: inventoryStockStatus(inventory)
            }));

        }
    },
    methods: {
        fieldErrors(index, field) {

            const error = this.errors[`inventory.${index}.${field}`];

            return Array.isArray(error) ? error : (error ? [error] : []);

        },
        updateRow(index, field, value) {

            const rows = this.rows.map((inventory, rowIndex) =>
                rowIndex === index ? {...inventory, [field]: value} : inventory
            );

            this.$emit("update:rows", rows);

        }
    }
};
</script>
