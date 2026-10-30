<template>
    <section class="br-inventory br-inventory-skeleton" role="status" aria-live="polite" :aria-label="`Cargando ${viewLabel}`">
        <span class="visually-hidden">Cargando {{ viewLabel }}</span>

        <div class="row align-items-end g-2 br-inventory__context" aria-hidden="true">
            <div class="col-12 col-md-6 col-lg-3 col-xl-3">
                <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                <span class="br-inventory-skeleton__control"></span>
            </div>
            <div :class="isReport ? 'col-12 col-md-6 col-lg-2 col-xl-2' : 'col-12 col-md-6 col-lg-3 col-xl-3'">
                <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                <span class="br-inventory-skeleton__control"></span>
            </div>
            <template v-if="isReport">
                <div class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                    <span class="br-inventory-skeleton__control"></span>
                </div>
                <div class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__label"></span>
                    <span class="br-inventory-skeleton__control"></span>
                </div>
            </template>
            <div :class="isReport ? 'col-12 col-md-6 col-lg-3 col-xl-3' : 'col-12 col-lg-6 col-xl-6'">
                <div class="br-inventory-skeleton__actions">
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                    <span v-if="!isReport" class="br-inventory-skeleton__line br-inventory-skeleton__button br-inventory-skeleton__button--create"></span>
                    <span class="br-inventory-skeleton__line br-inventory-skeleton__button"></span>
                </div>
            </div>
        </div>

        <div class="br-inventory__workspace" aria-hidden="true">
            <section class="br-inventory__content br-entity-list">
                <div class="table-responsive">
                    <table class="table br-entity-table br-inventory__table mb-0">
                        <thead class="br-table-header-surface">
                            <tr>
                                <th v-for="heading in headings" :key="heading">{{ heading }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in 7" :key="row" class="br-inventory-skeleton__row">
                                <td v-for="column in headings.length" :key="column">
                                    <span class="br-inventory-skeleton__line" :class="column === 1 ? 'br-inventory-skeleton__primary' : 'br-inventory-skeleton__cell'"></span>
                                    <span v-if="column === 1" class="br-inventory-skeleton__line br-inventory-skeleton__secondary"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="br-inventory-skeleton__pagination">
                <span v-for="pageNumber in 4" :key="pageNumber" class="br-inventory-skeleton__line br-inventory-skeleton__page"></span>
            </div>
        </div>
    </section>
</template>

<script>
const headingsByPage = {
    stock: ["Producto", "Stock actual", "Stock mínimo", "Almacenes", "Situación"],
    transfers: ["Fecha", "Producto", "Almacén de origen", "Almacén de destino", "Movimiento", "Cantidad", "Referencia"],
    guides: ["Fecha y número", "Almacén", "Tipo", "Detalle", "Referencia", "Estado"],
    kardex: ["Fecha y responsable", "Producto", "Movimiento", "Variación", "Saldo", "Motivo y origen"],
    valued: ["Fecha y producto", "Movimiento", "Variación", "Costo unitario", "Valor movimiento", "Valor resultante"]
};

const labelsByPage = {
    stock: "control de stock",
    transfers: "traslados",
    guides: "guías",
    kardex: "kardex",
    valued: "kardex valorizado"
};

export default {
    props: {page: {type: String, required: true}},
    computed: {
        headings() { return headingsByPage[this.page] || headingsByPage.stock; },
        viewLabel() { return labelsByPage[this.page] || labelsByPage.stock; },
        isReport() { return ["guides", "kardex", "valued"].includes(this.page); }
    }
};
</script>
