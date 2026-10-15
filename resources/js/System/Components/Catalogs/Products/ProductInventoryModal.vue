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
                        <strong class="br-product-inventory-view__name" v-text="record.name"></strong>
                        <div class="br-product-inventory-view__meta">
                            <span v-if="record.internal_code">Cód. interno: <strong v-text="record.internal_code"></strong></span>
                            <span v-if="record.barcode">Cód. barras: <strong v-text="record.barcode"></strong></span>
                            <span v-if="record.brand?.name">Marca: <strong v-text="record.brand.name"></strong></span>
                        </div>
                    </div>
                    <div class="br-product-inventory-view__total">
                        <span>Stock total</span>
                        <strong>{{ separatorNumber(summary.total) }} <small>unidades</small></strong>
                        <small v-if="summary.alerts" class="br-product-inventory-view__alert">
                            {{ summary.alerts }} {{ summary.alerts === 1 ? "almacén requiere atención" : "almacenes requieren atención" }}
                        </small>
                    </div>
                </div>

                <ProductWarehouseInventory v-if="inventoryRows.length" :rows="inventoryRows"/>

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
import ProductWarehouseInventory from "./ProductWarehouseInventory.vue";
import {summarizeProductStock} from "./productInventory.js";

let inventoryModalInstance = 0;

export default {
    name: "ProductInventoryModal",
    components: {FormModal, ProductWarehouseInventory},
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
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        }
    }
};
</script>
