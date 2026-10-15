<template>
    <FormModal
        :modal-id="modalId"
        title="INVENTARIO"
        modal-class="br-modal-standard"
        modal-size="modal-lg"
        :show-submit="false"
        close-button-text="Cerrar">
        <template #body>
            <div v-if="record" class="br-product-inventory-view">
                <div class="br-product-inventory-view__summary">
                    <div class="br-product-inventory-view__identity">
                        <strong v-text="record.name"></strong>
                        <span v-text="record.internal_code"></span>
                    </div>
                    <div class="br-product-inventory-view__total">
                        <span>Stock total</span>
                        <strong>{{ separatorNumber(summary.total) }} <small>unidades</small></strong>
                    </div>
                </div>

                <div v-if="inventoryRows.length" class="br-entity-inventory">
                    <div class="br-entity-inventory__head br-table-header-surface">
                        <span>Almacén</span>
                        <span>Stock actual</span>
                        <span>Stock mínimo</span>
                    </div>

                    <div
                        v-for="inventory in inventoryRows"
                        :key="inventory.warehouse_id"
                        :class="['br-entity-inventory__row', inventoryStockStatus(inventory).className]">
                        <div class="br-entity-inventory__warehouse">
                            <strong v-text="inventory.branch_name"></strong>
                            <span v-text="inventory.warehouse_name"></span>
                            <small :class="['br-entity-inventory-status', inventoryStockStatus(inventory).className]">
                                <i :class="inventoryStockStatus(inventory).icon" aria-hidden="true"></i>
                                <span v-text="inventoryStockStatus(inventory).text"></span>
                            </small>
                        </div>
                        <div class="br-entity-inventory__field">
                            <span class="br-entity-readonly-metric">
                                <span class="br-entity-inventory__mobile-label">Stock actual</span>
                                <strong v-text="separatorNumber(inventory.initial_stock)"></strong>
                                <small>unidades</small>
                            </span>
                        </div>
                        <div class="br-entity-inventory__field">
                            <span class="br-entity-readonly-metric">
                                <span class="br-entity-inventory__mobile-label">Stock mínimo</span>
                                <strong v-text="separatorNumber(inventory.minimum_stock)"></strong>
                                <small>unidades</small>
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="br-entity-inventory-empty">
                    <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                    <span>Este producto aún no tiene inventario registrado en almacenes.</span>
                </div>
            </div>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Utils from "@System/Helpers/Utils.js";
import FormModal from "@System/Components/Generics/FormModal.vue";
import {inventoryStockStatus, summarizeProductStock} from "./productInventory.js";

let inventoryModalInstance = 0;

export default {
    name: "ProductInventoryModal",
    components: {FormModal},
    data() {

        return {
            modalId: `product-inventory-view-${++inventoryModalInstance}`,
            record: null
        };

    },
    computed: {
        summary() {

            return summarizeProductStock(this.record);

        },
        inventoryRows() {

            return (this.record?.warehouse_items ?? [])
                .map(item => ({
                    warehouse_id: item.warehouse_id,
                    branch_name: item.warehouse?.branch?.name ?? "Sucursal no disponible",
                    warehouse_name: item.warehouse?.name ?? "Almacén no disponible",
                    initial_stock: Number(item.quantity ?? 0),
                    minimum_stock: Number(item.minimum_stock ?? 0)
                }))
                .sort((first, second) =>
                    first.branch_name.localeCompare(second.branch_name, "es") ||
                    first.warehouse_name.localeCompare(second.warehouse_name, "es")
                );

        }
    },
    methods: {
        open(record) {

            Alerts.tooltips({show: false});
            this.record = record;
            Alerts.modals({type: "show", id: this.modalId});

        },
        inventoryStockStatus,
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        }
    }
};
</script>
