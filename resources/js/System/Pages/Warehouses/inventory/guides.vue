<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InventorySkeleton v-if="initialLoading && skeletonEnabled" :page="page"/>

    <section v-else class="br-inventory">
        <div class="row align-items-end g-2 br-inventory__context">
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
                <div  class="br-inventory__kardex">
                    <section class="br-entity-list" aria-label="Guías de inventario">
                        <div class="table-responsive">
                            <table class="table br-entity-table br-inventory__kardex-table mb-0">
                                <thead class="br-table-header-surface">
                                    <tr>
                                        <th>Fecha y número</th>
                                        <th>Almacén</th>
                                        <th>Tipo</th>
                                        <th>Detalle</th>
                                        <th>Referencia</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loadingGuides">
                                        <td colspan="6" class="py-4"><Loader/></td>
                                    </tr>
                                    <template v-else-if="guideRecords.total > 0">
                                        <tr v-for="guide in guideRecords.data" :key="`guide-${guide.id}`">
                                            <td>
                                                <strong>{{ formatSimpleDate(guide.issue_date) }}</strong>
                                                <span class="br-inventory__meta">{{ guide.number }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ guide.warehouse?.name }}</strong>
                                                <span class="br-inventory__meta">{{ guide.warehouse?.branch?.name }}</span>
                                            </td>
                                            <td>
                                                <span :class="['br-inventory-movement', guide.guide_type === 'entry' ? 'is-entry' : 'is-exit']">
                                                    {{ guide.guide_type === "entry" ? "Entrada" : "Salida" }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ (guide.items || []).length }} producto{{ (guide.items || []).length === 1 ? "" : "s" }}</strong>
                                                <span class="br-inventory__meta">
                                                    {{ (guide.items || []).slice(0, 2).map(item => `${item.item?.name}: ${quantityWithUnit(item.quantity, item.item)}`).join(", ") }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ guide.reference || "-" }}</strong>
                                                <span class="br-inventory__meta">{{ guide.reason }}</span>
                                            </td>
                                            <td>
                                                <span class="br-status-label br-status-active">
                                                    {{ guide.status === "confirmed" ? "Confirmada" : guide.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="6" class="py-4">
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

            <nav
                v-if="!isCurrentViewLoading && currentRecords.total > 0 && currentRecords.links"
                class="d-flex justify-content-center mt-3"
                :aria-label="`Paginación de ${currentView.title.toLocaleLowerCase('es')}`">
                <Paginator :links="currentRecords.links" @clickPage="listCurrentView"/>
            </nav>
        </div>
    </section>
</template>

<script>
import inventoryPage from "./inventoryState.js";

export default inventoryPage;
</script>
