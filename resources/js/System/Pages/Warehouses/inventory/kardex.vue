<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <InventorySkeleton v-if="initialLoading" :page="page"/>

        <section v-else class="br-inventory">
            <div class="row align-items-end g-3 br-inventory__context">
                <div class="col-12 col-md-6 col-lg-3 col-xl-3 br-inventory__warehouse">
                    <label for="inventoryWarehouse" class="form-label">Almacén de trabajo</label>
                    <WarehouseSelect
                        id="inventoryWarehouse"
                        v-model="filters.warehouse"
                        :options="warehouses"
                        :disabled="isCurrentViewLoading"
                        :select-class="config.forms.classes.select2"/>
                </div>

                <SearchInput
                    v-if="['stock', 'transfers'].includes(activeView)"
                    ref="productSearch"
                    v-model.trim="filters.productSearch"
                    hasDiv
                    title="Producto"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Buscar o escanear producto"
                    :inputClass="['form-control']"
                    :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                    xl="3"
                    lg="3"
                    md="6"
                    @search="activeView === 'stock' ? handleProductSearch() : searchCurrentView()"
                    @clear="clearProductSearch"/>

                <div v-if="['kardex', 'valued'].includes(activeView)" class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <label for="movementType" class="form-label">Movimiento</label>
                    <v-select
                        id="movementType"
                        v-model="filters.movementType"
                        :options="movementTypes"
                        :class="config.forms.classes.select2"
                        :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                        :clearable="false"
                        :searchable="false"/>
                </div>

                <div v-if="activeView === 'guides'" class="col-12 col-md-6 col-lg-2 col-xl-2">
                    <label for="guideType" class="form-label">Tipo de guía</label>
                    <v-select
                        id="guideType"
                        v-model="filters.guideType"
                        :options="guideTypes"
                        :class="config.forms.classes.select2"
                        :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                        :clearable="false"
                        :searchable="false"/>
                </div>

                <template v-if="['kardex', 'valued', 'guides'].includes(activeView)">
                    <div class="col-12 col-md-6 col-lg-2 col-xl-2">
                        <label for="inventoryDateFrom" class="form-label">Desde</label>
                        <input id="inventoryDateFrom" v-model="filters.dateFrom" type="date" class="form-control" :disabled="isCurrentViewLoading || !filters.warehouse?.code">
                    </div>
                    <div class="col-12 col-md-6 col-lg-2 col-xl-2">
                        <label for="inventoryDateTo" class="form-label">Hasta</label>
                        <input id="inventoryDateTo" v-model="filters.dateTo" type="date" class="form-control" :disabled="isCurrentViewLoading || !filters.warehouse?.code">
                    </div>
                </template>

                <div :class="contextActionColumns">
                    <div class="br-filter-bar__actions br-inventory__context-actions">
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-search"
                            :disabled="!filters.warehouse?.code || isCurrentViewLoading"
                            @click="activeView === 'stock' ? handleProductSearch() : searchCurrentView()">
                            <span>Buscar</span>
                        </button>
                        <ClearFiltersButton
                            :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                            @clear="clearFilters"/>
                        <button
                            v-if="activeView === 'stock'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create"
                            :disabled="isCurrentViewLoading"
                            data-bs-toggle="modal"
                            data-bs-target="#inventoryMovementModal"
                            @click="prepareMovement()">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Registrar operación</span>
                        </button>
                        <button
                            v-if="activeView === 'transfers'"
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create waves-effect"
                            :disabled="isCurrentViewLoading"
                            data-bs-toggle="modal"
                            data-bs-target="#inventoryTransferModal"
                            @click="prepareTransfer">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Registrar traslado</span>
                        </button>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-actions"
                            :disabled="isCurrentViewLoading || !filters.warehouse?.code"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <span>Acciones</span>
                            <i class="fa-solid fa-chevron-down br-btn-actions__chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
                            <li>
                                <button type="button" class="dropdown-item" :disabled="isCurrentViewLoading || exporting || !filters.warehouse?.code" @click="exportCurrentView">
                                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                                    <span>Descargar Excel</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="br-inventory__workspace">
                <section class="br-inventory__content">
                    <div class="br-inventory__kardex">
                        <section class="br-entity-list" aria-label="Kardex">
                            <div class="table-responsive">
                                <table class="table br-entity-table br-inventory-table-headings br-inventory__kardex-table mb-0">
                                    <thead class="br-table-header-surface">
                                        <tr>
                                            <th scope="col">Producto</th>
                                            <th scope="col">Movimiento</th>
                                            <th scope="col">Variación</th>
                                            <th scope="col">Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="br-table-state" v-if="loadingKardex">
                                            <td colspan="4" class="py-4"><Loader/></td>
                                        </tr>
                                        <template v-else-if="kardexRecords.total > 0">
                                            <tr v-for="movement in kardexRecords.data" :key="movement.id">
                                                <td>
                                                    <ProductIdentity :product="movement.item"/>
                                                </td>
                                                <td>
                                                    <KardexMovementDetails
                                                        :movement="movement"
                                                        :date-label="formatDate(movement.created_at)"
                                                        :type-label="movementTypeLabel(movement.movement_type)"
                                                        :origin-label="originLabel(movement.origin_type)"/>
                                                </td>
                                                <td class="text-end">
                                                    <StockDifference :current="movement.quantity_change" :unit="movement.item?.measurement_unit || movement.item"/>
                                                </td>
                                                <td class="text-end">
                                                    <span class="br-inventory__stock-current">{{ quantityWithUnit(movement.quantity_after, movement.item) }}</span>
                                                    <span class="br-inventory__meta">Antes: {{ quantityWithUnit(movement.quantity_before, movement.item) }}</span>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr class="br-table-state" v-else>
                                            <td colspan="4" class="py-4">
                                                <WithoutData
                                                    class="br-entity-empty-state"
                                                    type="image"/>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </section>

                <Paginator :aria-label="`Paginación de ${currentView.title.toLocaleLowerCase('es')}`" :links="currentRecords.links" :pagination="currentRecords" :loading="isCurrentViewLoading" @clickPage="listCurrentView"/>
            </div>
        </section>
    </InitialLoadTransition>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
