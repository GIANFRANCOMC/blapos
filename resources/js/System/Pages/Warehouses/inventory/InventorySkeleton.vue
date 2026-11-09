<template>
    <section class="br-inventory br-inventory-skeleton" role="status" aria-live="polite" :aria-label="`Cargando ${viewLabel}`">
        <span class="visually-hidden">Cargando {{ viewLabel }}</span>

        <div class="row align-items-end g-3 br-inventory__context" aria-hidden="true">
            <div class="col-12 col-md-6 col-lg-3 col-xl-3">
                <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                <span class="br-inventory-skeleton__control"></span>
            </div>
            <div class="col-12 col-md-6"
                 :class="page === 'guides' ? 'col-lg-2' : 'col-lg-3'">
                <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                <span class="br-inventory-skeleton__control"></span>
            </div>
            <div v-if="hasStockFilters" class="col-12 col-md-6 col-lg-2 col-xl-2">
                <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                <span class="br-inventory-skeleton__control"></span>
            </div>
            <template v-if="isReport">
                <div v-for="field in isKardex ? 3 : 2" :key="field"
                     class="col-12 col-md-6"
                     :class="isKardex && field === 3 ? 'col-lg-3' : 'col-lg-2'">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                    <span class="br-inventory-skeleton__control"></span>
                </div>
            </template>
            <template v-if="page === 'stock'">
                <div v-for="field in 2" :key="field" class="col-12 col-sm-6 col-lg-2">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                    <span class="br-inventory-skeleton__control"></span>
                </div>
            </template>
            <template v-if="hasStockFilters">
                <div v-for="field in 2" :key="field" class="col-12 col-sm-6 col-lg-3">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                    <span class="br-inventory-skeleton__control"></span>
                </div>
            </template>
            <div v-if="page === 'stock'" class="col-auto flex-grow-1 d-flex flex-wrap align-items-end gap-2">
                <div class="br-inventory-skeleton__actions gap-2">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__clear"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                </div>
                <div class="br-inventory-skeleton__actions gap-2 ms-auto">
                    <span class="vr align-self-center"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button br-inventory-skeleton__button--create"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                </div>
            </div>
            <div v-else :class="isKardex ? 'col-12 col-lg-3' : page === 'guides' ? 'col-12 col-md-6 col-lg-3 col-xl-3' : 'col-12 col-lg-6 col-xl-6'">
                <div :class="['br-inventory-skeleton__actions', {'justify-content-start gap-lg-1': isKardex}]">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__clear"></span>
                    <span v-if="!isReport" class="br-inventory-skeleton__line br-inventory-skeleton__button br-inventory-skeleton__button--create"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                </div>
            </div>
        </div>

        <div class="br-inventory__workspace" aria-hidden="true">
            <section class="br-inventory__content br-entity-list">
                <div class="table-responsive">
                    <table
                        class="table br-entity-table br-inventory-table-headings mb-0"
                        :class="{
                            'br-inventory__table': !isKardex,
                            'br-inventory__table--single-warehouse': page === 'stock',
                            'br-inventory__kardex-table': isKardex,
                            'br-inventory__kardex-table--valued': page === 'valued'
                        }">
                        <colgroup v-if="page === 'stock'">
                            <col class="br-inventory__col-product">
                            <col class="br-inventory__col-current">
                            <col class="br-inventory__col-minimum">
                            <col class="br-inventory__col-difference">
                            <col class="br-inventory__col-status">
                            <col class="br-entity-table__col-actions">
                        </colgroup>
                        <thead class="br-table-header-surface">
                            <tr>
                                <th v-for="heading in headings" :key="heading">
                                    <template v-if="heading === 'Estado del stock'">Estado del<br>stock</template>
                                    <template v-else-if="heading">{{ heading }}</template>
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

const kardexHeadings = ["Producto", "Movimiento", "Variación"];
const headingsByPage = {
    stock: ["Producto", "Stock actual", "Stock mínimo", "Diferencia", "Estado del stock", ""],
    transfers: ["Fecha", "Producto", "Almacén de origen", "Almacén de destino", "Movimiento", "Cantidad", "Referencia"],
    guides: ["Fecha y número", "Almacén", "Tipo", "Detalle", "Referencia", "Estado"],
    kardex: [...kardexHeadings, "Saldo"],
    valued: [...kardexHeadings, "Costo unitario", "Valor movimiento", "Valor resultante"]
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
        headings() { return headingsByPage[this.page] || headingsByPage.stock; },
        viewLabel() { return labelsByPage[this.page] || labelsByPage.stock; },
        isKardex() { return this.page === "kardex" || this.page === "valued"; },
        hasStockFilters() { return this.page === "stock" || this.isKardex; },
        isReport() { return ["guides", "kardex", "valued"].includes(this.page); }
    }
};
</script>
