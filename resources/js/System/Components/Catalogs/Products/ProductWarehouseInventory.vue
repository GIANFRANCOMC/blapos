<template>
    <div class="br-entity-inventory">
        <div class="br-entity-inventory__head">
            <span>Almacén</span>
            <span>{{ currentLabel }}</span>
            <span>Stock mínimo</span>
        </div>

        <div
            v-for="item in displayRows"
            :key="item.inventory.warehouse_id"
            :class="['br-entity-inventory__row', item.status.className]">
            <div class="br-entity-inventory__warehouse">
                <strong v-text="item.inventory.warehouse_name"></strong>
                <span v-text="item.inventory.branch_name"></span>
                <small v-if="item.status.className === 'is-alert'" class="br-entity-inventory-status is-alert">
                    <i :class="item.status.icon" aria-hidden="true"></i>
                    <span v-text="item.status.text"></span>
                </small>
            </div>

            <div class="br-entity-inventory__field">
                <InputNumber
                    v-if="mode === 'edit' && !isUpdate"
                    :model-value="item.inventory.initial_stock"
                    :title="currentLabel"
                    :titleClass="['br-entity-inventory__mobile-label']"
                    :minValue="0"
                    :decimals="4"
                    hasTextBottom
                    :textBottomInfo="fieldErrors(item.index, 'initial_stock')"
                    @update:modelValue="updateRow(item.index, 'initial_stock', $event)"/>
                <span
                    v-else
                    class="br-entity-readonly-metric"
                    :aria-label="currentLabel"
                    :title="mode === 'edit' && isUpdate ? 'El stock actual se modifica desde Inventario.' : null">
                    <span class="br-entity-inventory__mobile-label" v-text="currentLabel"></span>
                    <strong v-text="formatQuantity(item.inventory.initial_stock)"></strong>
                </span>
            </div>

            <div class="br-entity-inventory__field">
                <InputNumber
                    v-if="mode === 'edit'"
                    :model-value="item.inventory.minimum_stock"
                    title="Stock mínimo"
                    :titleClass="['br-entity-inventory__mobile-label']"
                    :minValue="0"
                    :decimals="4"
                    hasTextBottom
                    :textBottomInfo="fieldErrors(item.index, 'minimum_stock')"
                    @update:modelValue="updateRow(item.index, 'minimum_stock', $event)"/>
                <span v-else class="br-entity-readonly-metric" aria-label="Stock mínimo">
                    <span class="br-entity-inventory__mobile-label">Stock mínimo</span>
                    <strong v-text="formatQuantity(item.inventory.minimum_stock)"></strong>
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import * as Utils from "@System/Helpers/Utils.js";
import InputNumber from "@System/Components/InputNumber.vue";
import {inventoryStockStatus} from "./productInventory.js";

export default {
    name: "ProductWarehouseInventory",
    components: {InputNumber},
    emits: ["update:rows"],
    props: {
        rows: {type: Array, required: true},
        mode: {type: String, default: "view"},
        isUpdate: {type: Boolean, default: false},
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
        formatQuantity(value) {

            return Utils.quantityWithUnit(value ?? 0, this.measurementUnit);

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
