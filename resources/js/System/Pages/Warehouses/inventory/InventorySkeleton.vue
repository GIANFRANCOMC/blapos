<template>
    <section class="br-inventory br-inventory-skeleton" role="status" aria-live="polite">
        <span class="visually-hidden">Cargando {{ viewLabel }}</span>

        <div class="row align-items-end g-3 br-inventory__context" aria-hidden="true">
            <div v-for="[label, columns] in filters" :key="label" :class="columns">
                <span class="form-label br-inventory-skeleton__label">{{ label }}</span>
                <span class="form-control br-inventory-skeleton__control">
                    <span class="br-inventory-skeleton__line"></span>
                </span>
            </div>

            <div :class="actionColumns">
                <div class="br-inventory__context-actions" :class="actionLayout">
                    <span class="br-btn br-btn-sm br-inventory-skeleton__button br-inventory-skeleton__button--icon"></span>
                    <span class="br-btn br-btn-sm br-inventory-skeleton__button br-inventory-skeleton__button--icon"></span>
                    <span v-if="page === 'transfers'" class="br-btn br-btn-sm br-inventory-skeleton__button">
                        <i class="fa-solid fa-plus"></i>
                        <span>Registrar traslado</span>
                    </span>
                    <span class="br-btn br-btn-sm br-btn-actions br-inventory-skeleton__button" :class="{'px-lg-2': isKardex}">
                        <span>Acciones</span>
                        <i class="fa-solid fa-chevron-down br-btn-actions__chevron"></i>
                    </span>
                </div>
                <div v-if="page === 'stock'" class="br-inventory__context-actions d-flex flex-nowrap align-items-stretch gap-2 ms-auto">
                    <span class="vr align-self-center"></span>
                    <span class="br-btn br-btn-sm br-inventory-skeleton__button">
                        <i class="fa-solid fa-plus"></i>
                        <span>Registrar operación</span>
                    </span>
                    <span class="br-btn br-btn-sm br-inventory-skeleton__button br-inventory-skeleton__button--icon"></span>
                </div>
            </div>
        </div>

        <div class="br-inventory__workspace" aria-hidden="true">
            <section class="br-inventory__content br-entity-list">
                <div class="table-responsive">
                    <table class="table br-entity-table br-inventory-table-headings mb-0" :class="tableClass">
                        <colgroup v-if="tableColumns">
                            <col v-for="column in tableColumns" :key="column" :class="column">
                        </colgroup>
                        <thead class="br-table-header-surface">
                            <tr>
                                <th v-for="heading in headings" :key="heading" scope="col"
                                    :class="{'text-nowrap px-0': page === 'stock' && heading === 'Estado del stock'}">
                                    <template v-if="heading">{{ heading }}</template>
                                    <span v-else class="visually-hidden">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="br-table-state">
                                <td :colspan="headings.length" class="py-4"><Loader/></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</template>

<script>
import Loader from "@System/Components/Loader.vue";

const wideField = "col-12 col-md-6 col-lg-3 col-xl-3";
const compactField = "col-12 col-md-6 col-lg-2 col-xl-2";
const compactStockField = "col-12 col-sm-6 col-lg-2";
const catalogField = "col-12 col-sm-6 col-lg-3";

const warehouse = ["Almacén de trabajo", wideField];
const product = ["Producto", wideField];
const dates = [["Desde", compactField], ["Hasta", compactField]];
const catalog = [["Categoría", catalogField], ["Marca", catalogField]];
const kardexFilters = [warehouse, product, ["Movimiento", compactField], ...dates, ["Realizado por", wideField], ...catalog];

const filtersByPage = {
    stock: [warehouse, product, ["Estado del stock", compactField], ["Stock mínimo", compactStockField], ["Último movimiento", compactStockField], ...catalog],
    transfers: [warehouse, product],
    guides: [warehouse, ["Tipo de guía", compactField], ...dates],
    kardex: kardexFilters,
    valued: kardexFilters
};

const kardexHeadings = ["Producto", "Movimiento", "Variación"];
const headingsByPage = {
    stock: ["Producto", "Stock actual", "Stock mínimo", "Diferencia", "Estado del stock", ""],
    transfers: ["Fecha y responsable", "Producto", "Almacén origen", "Almacén destino", "Movimiento", "Cantidad", "Referencia"],
    guides: ["Fecha y número", "Almacén", "Tipo", "Detalle", "Referencia", "Estado"],
    kardex: [...kardexHeadings, "Saldo"],
    valued: [...kardexHeadings, "Costo unitario", "Valor movimiento", "Valor resultante"]
};

const tableClasses = {
    stock: "br-inventory__table br-inventory__table--single-warehouse",
    transfers: "br-inventory-transfer__table",
    guides: "br-inventory__guides-table",
    kardex: "br-inventory__kardex-table",
    valued: "br-inventory__kardex-table br-inventory__kardex-table--valued"
};

const tableColumnsByPage = {
    stock: ["product", "current", "minimum", "difference", "status"]
        .map(column => `br-inventory__col-${column}`).concat("br-entity-table__col-actions"),
    transfers: ["date", "product", "source", "destination", "movement", "quantity", "reference"]
        .map(column => `br-inventory-transfer__col-${column}`)
};

const labelsByPage = {
    stock: "control de stock",
    transfers: "traslados",
    guides: "guías",
    kardex: "kardex",
    valued: "kardex valorizado"
};

export default {
    components: {Loader},
    props: {page: {type: String, required: true}},
    computed: {
        filters() { return filtersByPage[this.page]; },
        headings() { return headingsByPage[this.page]; },
        tableClass() { return tableClasses[this.page]; },
        tableColumns() { return tableColumnsByPage[this.page]; },
        viewLabel() { return labelsByPage[this.page]; },
        isKardex() { return this.page === "kardex" || this.page === "valued"; },
        actionColumns() {
            if(this.page === "stock") return "col-auto flex-grow-1 d-flex flex-wrap align-items-end gap-2";
            if(this.page === "transfers") return "col-12 col-lg-6 col-xl-6";
            if(this.isKardex) return "col-12 col-lg-3";
            return wideField;
        },
        actionLayout() {
            if(this.page === "stock") return "d-flex flex-nowrap align-items-stretch gap-2";
            return this.isKardex ? "br-filter-bar__actions gap-lg-1" : "br-filter-bar__actions";
        }
    }
};
</script>
