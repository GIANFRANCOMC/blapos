<template>
    <FormModal
        :modal-id="modalId"
        title="Inventario del producto"
        :modal-class="'fg-modal--inventory'"
        modal-size="modal-lg"
        :show-submit="false">
        <template #body>
                        <div v-if="record" class="fg-stack">
                <div class="fg-product-summary"><strong class="fg-modal__title">{{ record.name }}</strong><span class="fg-help">{{ record.internal_code }} · Código de barras {{ record.barcode || '—' }}</span><span v-if="record.brand?.name" class="fg-help">Marca: {{ record.brand.name }}</span></div>
                <div class="fg-stock-summary"><span class="fg-help">{{ inventoryRows.length }} almacenes<span v-if="summary.alerts"> · {{ summary.alerts }} requieren atención</span></span><span class="fg-stock-summary__total"><span class="fg-help">Stock total</span><strong><QuantityValue :value="summary.total" :unit="record.measurement_unit"/></strong></span></div>
                <ProductWarehouseInventory v-if="inventoryRows.length" :rows="inventoryRows" :measurement-unit="record.measurement_unit"/>
                <FgEmptyState v-else title="Sin existencias registradas" description="Este producto aún no tiene inventario registrado en almacenes."/>
                <p class="fg-help">Los movimientos se registran desde Inventario.</p>
            </div>

        </template>
    </FormModal>
</template>

<script>
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import * as Alerts from "@System/Helpers/Alerts.js";
import FormModal from "@System/Components/Generics/FormModal.vue";
import QuantityValue from "@System/Components/Generics/QuantityValue.vue";
import ProductWarehouseInventory from "./ProductWarehouseInventory.vue";
import {summarizeProductStock} from "./productInventory.js";

let inventoryModalInstance = 0;

export default {
    name: "ProductInventoryModal",
    components: {FgEmptyState,FormModal, ProductWarehouseInventory, QuantityValue},
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

        }
    }
};
</script>
