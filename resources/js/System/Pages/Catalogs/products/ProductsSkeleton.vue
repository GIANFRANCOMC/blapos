<template>
    <main class="br-entity br-products-skeleton" role="status" aria-live="polite" aria-label="Cargando productos">
        <span class="visually-hidden">Cargando productos</span>

        <section class="br-filter-bar br-products-skeleton__filters" aria-hidden="true">
            <div class="br-products-skeleton__filter">
                <span class="br-products-skeleton__line br-products-skeleton__label"></span>
                <span class="br-products-skeleton__control">
                    <span class="br-products-skeleton__line br-products-skeleton__select-value"></span>
                </span>
            </div>
            <div class="br-products-skeleton__filter">
                <span class="br-products-skeleton__line br-products-skeleton__label"></span>
                <span class="br-products-skeleton__control">
                    <span class="br-products-skeleton__line br-products-skeleton__search-value"></span>
                </span>
            </div>
            <div class="br-products-skeleton__filter br-products-skeleton__filter--actions">
                <div class="br-filter-bar__actions">
                    <span class="br-products-skeleton__line br-products-skeleton__button"></span>
                    <span class="br-products-skeleton__line br-products-skeleton__button br-products-skeleton__button--add"></span>
                    <span class="br-products-skeleton__line br-products-skeleton__button br-products-skeleton__button--more"></span>
                </div>
            </div>
        </section>

        <section class="br-entity-list" aria-hidden="true">
            <div class="table-responsive">
                <table class="table br-entity-table br-entity-table--commercial-headings mb-0">
                    <colgroup>
                        <col class="br-entity-table__col-identification">
                        <col class="br-entity-table__col-product">
                        <col class="br-entity-table__col-price">
                        <col class="br-entity-table__col-status">
                        <col class="br-entity-table__col-actions">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Identificación</th>
                            <th class="text-center">Producto</th>
                            <th class="text-center">Precio</th>
                            <th class="text-center">Estado</th>
                            <th><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="br-table-state">
                            <td colspan="5" class="py-4"><Loader/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script>
import Loader from "@System/Components/Loader.vue";

export default {components: {Loader}};
</script>
