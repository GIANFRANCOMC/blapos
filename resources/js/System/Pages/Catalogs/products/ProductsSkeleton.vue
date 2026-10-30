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
                <table class="table br-entity-table mb-0">
                    <colgroup>
                        <col class="br-entity-table__col-identification">
                        <col class="br-entity-table__col-product">
                        <col class="br-entity-table__col-price">
                        <col class="br-entity-table__col-inventory">
                        <col class="br-entity-table__col-status">
                        <col class="br-entity-table__col-actions">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Identificación</th>
                            <th class="text-center">Producto</th>
                            <th class="text-end">Precio</th>
                            <th>Inventario</th>
                            <th class="text-center">Estado</th>
                            <th><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="index in 8" :key="index" class="br-products-skeleton__row">
                            <td class="br-entity-table__identification-cell">
                                <span class="br-products-skeleton__identifiers">
                                    <span class="br-products-skeleton__line br-products-skeleton__identifier-label"></span>
                                    <span class="br-products-skeleton__line br-products-skeleton__identifier-value"></span>
                                    <span class="br-products-skeleton__line br-products-skeleton__identifier-label"></span>
                                    <span class="br-products-skeleton__line br-products-skeleton__identifier-value br-products-skeleton__identifier-value--short"></span>
                                </span>
                            </td>
                            <td>
                                <span class="br-products-skeleton__cell-copy">
                                    <span class="br-products-skeleton__line br-products-skeleton__name"></span>
                                    <span class="br-products-skeleton__line br-products-skeleton__brand"></span>
                                    <span class="br-products-skeleton__line br-products-skeleton__description"></span>
                                </span>
                            </td>
                            <td>
                                <span class="br-products-skeleton__prices">
                                    <span class="br-products-skeleton__line br-products-skeleton__price"></span>
                                    <span class="br-products-skeleton__line br-products-skeleton__price br-products-skeleton__price--short"></span>
                                </span>
                            </td>
                            <td><span class="br-products-skeleton__line br-products-skeleton__stock"></span></td>
                            <td><span class="br-products-skeleton__line br-products-skeleton__badge"></span></td>
                            <td><span class="br-products-skeleton__line br-products-skeleton__menu"></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="br-products-skeleton__pagination" aria-hidden="true">
            <span v-for="index in 4" :key="index" class="br-products-skeleton__line br-products-skeleton__page"></span>
        </div>
    </main>
</template>
