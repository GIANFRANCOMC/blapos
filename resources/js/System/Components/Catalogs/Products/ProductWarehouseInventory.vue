<template>
    <div class="br-entity-inventory">
        <div class="br-entity-inventory__head">
            <span>Sede</span>
            <span>Almacén</span>
            <span>{{ currentLabel }}</span>
            <span>Stock mínimo</span>
            <span>Estado del stock</span>
        </div>

        <div
            v-for="item in displayRows"
            :key="item.inventory.warehouse_id"
            class="br-entity-inventory__row">
            <div class="br-entity-inventory__branch">
                <span class="br-entity-inventory__mobile-label">Sede</span>
                <span v-text="item.inventory.branch_name"></span>
            </div>
            <div class="br-entity-inventory__warehouse">
                <span class="br-entity-inventory__mobile-label">Almacén</span>
                <span v-text="item.inventory.warehouse_name"></span>
            </div>

            <div class="br-entity-inventory__field">
                <InputNumber
                    v-if="mode === 'edit' && !isUpdate"
                    :model-value="item.inventory.initial_stock"
                    :title="currentLabel"
                    :placeholder="currentLabel"
                    :aria-label="`${currentLabel} · ${item.inventory.branch_name} · ${item.inventory.warehouse_name}`"
                    :titleClass="['br-entity-inventory__mobile-label']"
                    :minValue="0"
                    hasTextBottom
                    :textBottomInfo="fieldErrors(item.index, 'initial_stock')"
                    @update:modelValue="updateRow(item.index, 'initial_stock', $event)"/>
                <span
                    v-else
                    class="br-entity-readonly-metric"
                    :aria-label="currentLabel"
                    :title="mode === 'edit' && isUpdate ? 'El stock actual se modifica desde Inventario.' : null">
                    <span class="br-entity-inventory__mobile-label" v-text="currentLabel"></span>
                    <QuantityValue class="br-inventory__stock-current" :value="item.inventory.initial_stock" :unit="measurementUnit"/>
                </span>
            </div>

            <div class="br-entity-inventory__field">
                <InputNumber
                    v-if="mode === 'edit'"
                    :model-value="item.inventory.minimum_stock"
                    title="Stock mínimo"
                    placeholder="Stock mínimo"
                    :aria-label="`Stock mínimo · ${item.inventory.branch_name} · ${item.inventory.warehouse_name}`"
                    :titleClass="['br-entity-inventory__mobile-label']"
                    :minValue="0"
                    hasTextBottom
                    :textBottomInfo="fieldErrors(item.index, 'minimum_stock')"
                    @update:modelValue="updateRow(item.index, 'minimum_stock', $event)"/>
                <span v-else class="br-entity-readonly-metric" aria-label="Stock mínimo">
                    <span class="br-entity-inventory__mobile-label">Stock mínimo</span>
                    <QuantityValue :value="item.inventory.minimum_stock" :unit="measurementUnit"/>
                </span>
            </div>

            <div class="br-entity-inventory__status" :class="item.status && ['br-inventory__situation', `br-inventory__situation--${item.status.code}`]">
                <span class="br-entity-inventory__mobile-label">Estado del stock</span>
                <span v-if="item.status" v-text="item.status.text"></span>
                <span v-else class="text-muted" aria-label="Estado del stock pendiente">—</span>
            </div>
        </div>
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
        updateRow(index, field, value) {

            const rows = this.rows.map((inventory, rowIndex) =>
                rowIndex === index ? {...inventory, [field]: value} : inventory
            );

            this.$emit("update:rows", rows);

        }
    }
};
</script>
