<template>
    <FormModal
        modal-id="inventoryStockEditorModal"
        title="EDITAR STOCK"
        modal-size="modal-lg"
        :keyboard="false"
        :close-disabled="busy"
        :submit-disabled="busy || !changed"
        submit-button-text="Guardar cambios"
        @submit="save">
        <template #header>
            <ProductIdentity v-if="record" variant="expandable" class="mt-1" :product="record"/>
        </template>
        <template #body>
            <p class="br-operational-scope mb-3">
                <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                <span>{{ warehouse?.displayLabel || warehouse?.label }}</span>
            </p>
            <div class="row g-3">
                <InputNumber
                    v-model="form.quantity"
                    has-div is-required has-text-bottom
                    title="Stock actual"
                    :title-class="['form-label', 'colon-at-end']"
                    :input-class="['form-control']"
                    :min-value="Math.min(0, original.quantity)"
                    :has-negative="original.quantity < 0"
                    :disabled="busy"
                    :text-bottom-info="errors.quantity || []"
                    md="6" lg="6" xl="6"
                    @enter-key-pressed="save"/>
                <InputNumber
                    v-model="form.minimum_stock"
                    has-div is-required has-text-bottom
                    title="Stock mínimo"
                    :title-class="['form-label', 'colon-at-end']"
                    :input-class="['form-control']"
                    :min-value="0"
                    :disabled="busy"
                    :text-bottom-info="errors.minimum_stock || []"
                    md="6" lg="6" xl="6"
                    @enter-key-pressed="save"/>
                <div class="col-12">
                    <InputText
                        v-model="form.reason"
                        title="Motivo del ajuste"
                        :title-class="['form-label', 'colon-at-end']"
                        :input-class="['form-control']"
                        placeholder="Opcional"
                        maxlength="100"
                        :disabled="busy"/>
                </div>
            </div>
            <p v-if="error" class="text-danger mt-3 mb-0" role="alert">{{ error }}</p>
        </template>
        <template #footerClose>
            <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal" :disabled="busy">Cancelar</button>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import FormModal from "@System/Components/Generics/FormModal.vue";
import ProductIdentity from "@System/Components/Generics/ProductIdentity.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputText from "@System/Components/InputText.vue";

export default {
    components: {FormModal, InputNumber, InputText, ProductIdentity},
    props: {refresh: {type: Function, required: true}},
    data() {
        return {
            record: null, warehouse: null, original: {quantity: 0, minimum_stock: 0},
            form: {quantity: 0, minimum_stock: 0, reason: ""}, errors: {}, error: "", busy: false, returnModalId: null
        };
    },
    computed: {
        changed() {
            return Number(this.form.quantity) !== this.original.quantity
                || Number(this.form.minimum_stock) !== this.original.minimum_stock;
        }
    },
    mounted() {
        document.getElementById("inventoryStockEditorModal")?.addEventListener("hidden.bs.modal", this.onHidden);
    },
    beforeUnmount() {
        this.returnModalId = null;
        document.getElementById("inventoryStockEditorModal")?.removeEventListener("hidden.bs.modal", this.onHidden);
    },
    methods: {
        async open({record, warehouse, fromAlerts = false}) {
            if(this.busy || !warehouse?.code || !record?.id) return;
            this.record = record;
            this.warehouse = warehouse;
            this.original = {quantity: Number(record.stock_quantity), minimum_stock: Number(record.minimum_stock)};
            this.form = {...this.original, reason: ""};
            this.errors = {};
            this.error = "";
            this.returnModalId = fromAlerts ? "inventoryStockAlertsModal" : null;
            if(this.returnModalId) {
                const modal = document.getElementById(this.returnModalId);
                if(modal?.classList.contains("show")) {
                    await new Promise(resolve => {
                        modal.addEventListener("hidden.bs.modal", resolve, {once: true});
                        Alerts.modals({type: "hide", id: this.returnModalId});
                    });
                }
            }
            await this.$nextTick();
            Alerts.modals({id: "inventoryStockEditorModal"});
        },
        onHidden() {
            const returnModalId = this.returnModalId;
            this.returnModalId = null;
            if(returnModalId) Alerts.modals({id: returnModalId});
        },
        async save() {
            if(this.busy || !this.record || !this.changed) return;
            this.errors = {};
            this.error = "";
            for(const field of ["quantity", "minimum_stock"]) {
                const preservesNegative = field === "quantity" && Number(this.form[field]) === this.original.quantity;
                if(this.form[field] === "" || this.form[field] == null || !Number.isFinite(Number(this.form[field])) || (Number(this.form[field]) < 0 && !preservesNegative)) {
                    this.errors[field] = ["Ingresa un número mayor o igual a cero."];
                }
            }
            if(Object.keys(this.errors).length) return;
            this.busy = true;
            let loading = false;
            try {
                const confirmed = await Alerts.confirmAction({
                    title: "¿Actualizar stock?",
                    text: `Se actualizará el stock de ${this.record.name} en ${this.warehouse.displayLabel || this.warehouse.label}.`,
                    confirmButtonText: "Guardar cambios"
                });
                if(!confirmed) return;
                Alerts.loading({message: "Actualizando stock"});
                loading = true;
                const result = await Requests.post({
                    route: Requests.config({entity: "stocks_management", type: "stockLevels"}),
                    data: {
                        warehouse_id: this.warehouse.code, item_id: this.record.id,
                        quantity: this.form.quantity, minimum_stock: this.form.minimum_stock,
                        expected_quantity: this.original.quantity, expected_minimum_stock: this.original.minimum_stock,
                        reason: this.form.reason
                    }
                });
                if(!Requests.valid({result})) {
                    this.errors = result?.errors || result?.data?.errors || {};
                    this.error = result?.data?.msg || result?.data?.message || "No fue posible actualizar el stock.";
                    if(result?.code === 409 && result.data?.data) {
                        this.original = {quantity: Number(result.data.data.quantity), minimum_stock: Number(result.data.data.minimum_stock)};
                        this.form = {...this.original, reason: this.form.reason};
                    }
                    return;
                }
                this.original = {quantity: Number(result.data.data.quantity), minimum_stock: Number(result.data.data.minimum_stock)};
                try {
                    const current = await this.refresh({warehouseId: this.warehouse.code, fromAlerts: Boolean(this.returnModalId)});
                    if(current === false) this.returnModalId = null;
                } catch {
                    Alerts.toastrs({type: "warning", subtitle: "El stock se guardó. Vuelve a buscar para actualizar los listados."});
                }
                Alerts.modals({type: "hide", id: "inventoryStockEditorModal"});
                Alerts.toastrs({type: "success", subtitle: "Stock actualizado correctamente."});
            } catch {
                this.error = "No fue posible completar la actualización. Comprueba la conexión y vuelve a intentarlo.";
            } finally {
                if(loading) Alerts.close();
                this.busy = false;
            }
        }
    }
};
</script>
