<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <main class="br-service-page" :class="{'br-service-page--sessions br-entity': !isRestaurant}">
        <div class="br-service-toolbar" :class="{'br-service-toolbar--sessions br-filter-bar': !isRestaurant}">
            <div class="br-service-toolbar__field">
                <label class="form-label colon-at-end">Sucursal</label>
                <v-select
                    v-model="selectedBranch"
                    :options="branchOptions"
                    :clearable="false"
                    :searchable="true"
                    :disabled="loading"
                    @option:selected="handleBranchChange"/>
            </div>
            <div v-if="!isRestaurant" class="br-service-toolbar__field">
                <label class="form-label colon-at-end">Estado</label>
                <v-select
                    v-model="selectedStatus"
                    :options="statusOptions"
                    :clearable="false"
                    :searchable="false"
                    :disabled="loading || !selectedBranch"
                    @option:selected="getSessions()"/>
            </div>
            <div class="br-service-toolbar__actions">
                <button
                    v-if="isRestaurant"
                    type="button"
                    class="br-btn br-btn-secondary"
                    @click="openFloorModal">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                    <span>Agregar piso</span>
                </button>
                <button
                    v-if="isRestaurant"
                    type="button"
                    class="br-btn br-btn-action-create"
                    :disabled="!selectedFloor"
                    :title="selectedFloor ? 'Agregar mesa al piso actual' : 'Primero agrega un piso'"
                    @click="openStationModal">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Agregar mesa</span>
                </button>
                <button
                    v-else
                    type="button"
                    class="br-btn br-btn-sm br-btn-action-open-create"
                    :disabled="loading || !selectedBranch"
                    @click="openSessionModal()">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Iniciar atención</span>
                </button>
            </div>
        </div>

        <Loader v-if="isRestaurant && loading"/>

        <template v-if="isRestaurant && !loading">
            <template v-if="floors.length">
                <div class="br-service-floor-nav">
                    <div
                        v-for="floor in floors"
                        :key="floor.id"
                        class="br-service-floor-nav__group">
                        <button
                            type="button"
                            class="br-service-floor-nav__item"
                            :class="{'is-active': selectedFloor?.id === floor.id}"
                            @click="selectFloor(floor)">
                            <span>{{ floor.name }}</span>
                            <small>{{ floor.stations_count || 0 }}</small>
                        </button>
                        <button
                            type="button"
                            class="br-service-floor-nav__edit"
                            data-bs-toggle="tooltip"
                            title="Editar piso"
                            aria-label="Editar piso"
                            @click.stop="openFloorModal(floor)">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="br-service-floor-meta">
                    <div class="br-service-legend" aria-label="Leyenda de disponibilidad">
                        <span><i class="is-available"></i> Disponible</span>
                        <span><i class="is-pending"></i> Pendiente</span>
                        <span><i class="is-progress"></i> En atención</span>
                    </div>
                    <small><i class="fa-solid fa-arrows-up-down-left-right" aria-hidden="true"></i> Arrastra desde el asa para ordenar el plano.</small>
                </div>

                <div class="br-service-floor-plan-scroll">
                    <div
                        ref="floorPlan"
                        class="br-service-floor-plan"
                        :style="{backgroundColor: selectedFloor?.background_color || '#f7f8fa'}">
                    <article
                        v-for="station in stations"
                        :key="station.id"
                        class="br-service-map-station"
                        :class="[stationClass(station), `is-${station.shape || 'round'}`]"
                        :style="stationPositionStyle(station)">
                        <button
                            type="button"
                            class="br-service-map-station__drag"
                            title="Mover mesa"
                            aria-label="Mover mesa"
                            @pointerdown.prevent="startStationDrag($event, station)">
                            <i class="fa-solid fa-grip" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            class="br-service-map-station__color"
                            title="Cambiar color"
                            aria-label="Cambiar color"
                            @click.stop="cycleStationColor(station)">
                            <i class="fa-solid fa-palette" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            class="br-service-map-station__edit"
                            title="Editar mesa"
                            aria-label="Editar mesa"
                            @click.stop="openStationModal(station)">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>
                        <button
                            type="button"
                            class="br-service-map-station__body"
                            @click="station.active_session ? selectSession(station.active_session) : openSessionModal(station)">
                            <strong>{{ station.name }}</strong>
                            <small>{{ station.capacity }} personas</small>
                            <span>{{ station.active_session ? elapsedLabel(station.active_session) : 'Disponible' }}</span>
                        </button>
                    </article>

                        <div v-if="!stations.length" class="br-service-floor-plan__empty">
                            <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                            <strong>Este piso aún no tiene mesas</strong>
                            <span>Agrega una mesa para comenzar a diseñar la distribución.</span>
                        </div>
                    </div>
                </div>
            </template>
            <WithoutData
                v-else
                text="Aún no hay pisos registrados"
                description="Crea el primer piso o zona para ubicar las mesas de esta sucursal."/>
        </template>

        <template v-if="!isRestaurant">
            <div v-if="reports" class="br-service-report-grid" aria-label="Resumen del mes">
                <article>
                    <span>Atenciones del mes</span>
                    <strong>{{ reports.summary.total_sessions }}</strong>
                    <small>{{ reports.summary.open_sessions }} pendientes o en curso</small>
                </article>
                <article>
                    <span>Cumplimiento del plazo</span>
                    <strong>{{ reports.summary.sla_compliance_rate === null ? '—' : `${reports.summary.sla_compliance_rate}%` }}</strong>
                    <small>{{ reports.summary.sla_compliance_rate === null ? 'Sin atenciones con plazo definido' : `${reports.summary.sla_late_sessions} fuera de plazo` }}</small>
                </article>
                <article>
                    <span>Tiempo promedio</span>
                    <strong>{{ minutesLabel(reports.summary.average_duration_minutes) }}</strong>
                    <small>{{ reports.summary.completed_sessions }} finalizadas</small>
                </article>
                <article>
                    <span>Comisiones estimadas</span>
                    <strong>S/ {{ money(reports.summary.commission_total) }}</strong>
                    <small>Atenciones del mes</small>
                </article>
            </div>

            <section class="br-entity-list br-service-list" aria-label="Listado de atenciones">
                <div class="table-responsive">
                <table class="table br-entity-table br-service-list__table mb-0">
                    <colgroup>
                        <col style="width: 15%;">
                        <col style="width: 20%;">
                        <col style="width: 15%;">
                        <col style="width: 14%;">
                        <col style="width: 13%;">
                        <col style="width: 9%;">
                        <col style="width: 8%;">
                        <col style="width: 6%;">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Identificación</th>
                            <th>Servicio</th>
                            <th>Cliente</th>
                            <th>Responsable</th>
                            <th>Inicio</th>
                            <th>Tiempo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="8" class="py-4"><Loader/></td>
                        </tr>
                        <template v-else-if="sessions.data.length">
                        <tr v-for="session in sessions.data" :key="session.id">
                            <td class="br-entity-table__identification-cell">
                                <div class="br-entity-identifiers br-entity-identifiers--plain">
                                    <div class="br-entity-identifier">
                                        <span class="br-entity-identifier__label colon-at-end">Atención</span>
                                        <span class="br-entity-identifier__value">
                                            <span class="br-entity-code">{{ session.reference || '—' }}</span>
                                            <span v-if="session.reference" class="br-entity-identifier__actions">
                                                <CopyButton :value="session.reference" label="Número de atención"/>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="br-entity-table__name">{{ session.items?.[0]?.name || 'Sin servicio inicial' }}</span>
                                <span v-if="session.items?.length > 1" class="br-entity-table__meta">+ {{ session.items.length - 1 }} {{ session.items.length === 2 ? 'servicio' : 'servicios' }}</span>
                            </td>
                            <td><span :class="{'br-service-list__muted': !session.customer}">{{ session.customer?.name || 'Cliente general' }}</span></td>
                            <td><span :class="{'br-service-list__muted': !session.assigned_user}">{{ session.assigned_user?.name || 'Sin asignar' }}</span></td>
                            <td class="br-service-list__date">{{ formatDateTime(session.started_at || session.created_at) }}</td>
                            <td class="br-service-list__time">{{ elapsedLabel(session) }}</td>
                            <td class="text-center">
                                <StatusBadge :status="session.status" :formatted-status="statusLabel(session.status)" :custom-variants="sessionStatusVariants"/>
                            </td>
                            <td class="text-center">
                                <TableActionsDropdown :label="`Acciones de ${session.reference}`">
                                    <li><button type="button" class="dropdown-item" @click="selectSession(session)"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span>Gestionar atención</span></button></li>
                                </TableActionsDropdown>
                            </td>
                        </tr>
                        </template>
                        <tr v-else>
                            <td colspan="8" class="py-4">
                                <WithoutData v-if="selectedBranch" class="br-entity-empty-state" type="image"/>
                                <WithoutData v-else class="br-entity-empty-state" type="image" text="No hay sucursales disponibles" description="Solicita acceso a una sucursal para gestionar atenciones."/>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </section>
            <nav v-if="!loading && sessions.total > 0" class="br-service-list__pagination" aria-label="Paginación de atenciones">
                <Paginator :links="sessions.links" @clickPage="getSessions"/>
            </nav>
        </template>

        <div v-if="selectedSession" class="br-service-detail-backdrop" aria-hidden="true" @click="selectedSession = null"></div>
        <aside v-if="selectedSession" class="br-service-detail" :class="{'br-service-detail--sessions': !isRestaurant}" aria-label="Detalle de la atención">
            <header class="br-service-detail__header">
                <div>
                    <small>Atención {{ selectedSession.reference }}</small>
                    <h2>{{ selectedSession.station?.name || selectedSession.items?.[0]?.name || 'Atención de servicio' }}</h2>
                    <p>
                        {{ selectedSession.customer?.name || 'Cliente general' }}
                        <span v-if="selectedSession.assigned_user"> · {{ selectedSession.assigned_user.name }}</span>
                    </p>
                </div>
                <button type="button" class="br-modal-close" aria-label="Cerrar detalle" @click="selectedSession = null">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </header>

            <div class="br-service-detail__content">
            <div class="br-service-detail__metrics">
                <span><small>Estado</small><strong>{{ statusLabel(selectedSession.status) }}</strong></span>
                <span><small>Tiempo</small><strong>{{ elapsedLabel(selectedSession) }}</strong></span>
                <span><small>{{ isRestaurant ? 'Detalles' : 'Servicios' }}</small><strong>{{ selectedSession.items?.length || 0 }}</strong></span>
            </div>

            <section v-if="['pending', 'in_progress'].includes(selectedSession.status)" class="br-service-detail__section" aria-label="Gestión de la atención">
                <h3>Gestión de la atención</h3>
                <div class="br-service-detail__operations">
                    <div class="br-service-detail__field">
                        <label class="form-label colon-at-end">Responsable</label>
                        <v-select
                            v-model="operationForm.user"
                            :options="userOptions"
                            :clearable="true"
                            :searchable="true"
                            placeholder="Sin asignar"/>
                    </div>
                    <button
                        type="button"
                        class="br-btn br-btn-sm br-btn-secondary"
                        :disabled="saving || !operationForm.user"
                        @click="reassignSession">
                        Reasignar
                    </button>
                </div>
                <div class="br-service-detail__field">
                    <label class="form-label colon-at-end">Motivo o nota</label>
                    <input
                        v-model.trim="operationForm.note"
                        type="text"
                        class="form-control"
                        maxlength="500"
                        placeholder="Opcional para pausa; obligatorio para cancelar"/>
                </div>
                <div class="br-service-detail__quick-actions">
                    <button
                        v-if="!isSessionPaused(selectedSession)"
                        type="button"
                        class="br-btn br-btn-sm br-btn-action-import"
                        :disabled="saving"
                        @click="pauseSession">
                        Pausar
                    </button>
                    <button
                        v-else
                        type="button"
                        class="br-btn br-btn-sm br-btn-success"
                        :disabled="saving"
                        @click="resumeSession">
                        Reanudar
                    </button>
                    <button
                        type="button"
                        class="br-btn br-btn-sm br-btn-danger"
                        :disabled="saving || !operationForm.note"
                        @click="cancelSession">
                        Cancelar atención
                    </button>
                </div>
            </section>

            <section class="br-service-detail__section" aria-label="Servicios de la atención">
                <h3>{{ isRestaurant ? 'Detalle de la mesa' : 'Servicios de la atención' }}</h3>
                <div v-if="['pending', 'in_progress'].includes(selectedSession.status)" class="br-service-add-item">
                    <div class="br-service-detail__field">
                        <label class="form-label colon-at-end">{{ isRestaurant ? 'Producto o servicio' : 'Servicio' }}</label>
                        <v-select
                            v-model="detailForm.item"
                            :options="itemOptions"
                            :clearable="false"
                            :searchable="true"
                            :filterable="false"
                            @search="searchItems"
                            placeholder="Seleccionar"/>
                    </div>
                    <div class="br-service-detail__field">
                        <label class="form-label colon-at-end">Responsable</label>
                        <v-select
                            v-model="detailForm.user"
                            :options="userOptions"
                            :clearable="true"
                            :searchable="true"
                            placeholder="Sin asignar"/>
                    </div>
                    <button
                        type="button"
                        class="br-btn br-btn-action-create"
                        :disabled="saving || !detailForm.item"
                        @click="addSessionItem">
                        Agregar
                    </button>
                </div>

            <div class="br-service-detail__items">
                <article v-for="item in selectedSession.items" :key="item.id">
                    <div>
                        <strong>{{ item.name }}</strong>
                        <small>{{ item.assigned_user?.name || 'Sin responsable' }} · {{ elapsedLabel(item) }}</small>
                    </div>
                    <span v-if="isRestaurant" class="br-status-label" :class="preparationStatusClass(item.preparation_status)">
                        {{ preparationStatusLabel(item.preparation_status) }}
                    </span>
                    <span v-else class="br-status-label" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                    <button
                        v-if="isRestaurant && nextPreparationAction(item)"
                        type="button"
                        class="br-btn br-btn-sm br-btn-secondary"
                        @click="changePreparationStatus(item, nextPreparationAction(item).status)">
                        {{ nextPreparationAction(item).label }}
                    </button>
                    <span v-else-if="isRestaurant" class="br-status-label br-status-completed">Entregado</span>
                    <button
                        v-if="item.status === 'pending' && ['pending', 'in_progress'].includes(selectedSession.status)"
                        type="button"
                        class="br-btn br-btn-sm br-btn-secondary"
                        @click="changeItemStatus(item, 'start')">
                        Iniciar
                    </button>
                    <button
                        v-else-if="item.status === 'in_progress' && ['pending', 'in_progress'].includes(selectedSession.status)"
                        type="button"
                        class="br-btn br-btn-sm br-btn-success"
                        @click="changeItemStatus(item, 'complete')">
                        Finalizar
                    </button>
                </article>
                <p v-if="!selectedSession.items?.length" class="br-service-detail__empty">Aún no hay {{ isRestaurant ? 'productos' : 'servicios' }} en esta atención.</p>
            </div>
            </section>

            <section v-if="selectedSession.events?.length" class="br-service-timeline" aria-label="Línea de tiempo">
                <h3>Línea de tiempo</h3>
                <ol>
                    <li v-for="event in selectedSession.events" :key="event.id">
                        <span></span>
                        <div>
                            <strong>{{ eventLabel(event) }}</strong>
                            <small>
                                {{ formatDateTime(event.occurred_at) }}
                                <template v-if="event.user"> · {{ event.user.name }}</template>
                            </small>
                            <p v-if="event.note">{{ event.note }}</p>
                        </div>
                    </li>
                </ol>
            </section>
            </div>

            <footer class="br-service-detail__footer">
                <button
                    v-if="selectedSession.status === 'pending'"
                    type="button"
                    class="br-btn br-btn-action-create"
                    :disabled="saving"
                    @click="changeSessionStatus('start')">
                    <i class="fa-solid fa-play" aria-hidden="true"></i>
                    <span>Iniciar atención</span>
                </button>
                <button
                    v-if="selectedSession.items?.length"
                    type="button"
                    class="br-btn br-btn-success"
                    :disabled="saving"
                    @click="goToPos">
                    <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                    <span>Cobrar en POS</span>
                </button>
                <button
                    v-if="selectedSession.status === 'in_progress'"
                    type="button"
                    class="br-btn br-btn-primary"
                    :disabled="saving"
                    @click="changeSessionStatus('complete')">
                    <span>Finalizar sin venta</span>
                </button>
            </footer>
        </aside>
    </main>

    <div id="brServiceFloorModal" class="modal fade br-entity-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Restaurante POS</p>
                        <h2 class="modal-title br-entity-modal__title">{{ editingFloorId ? 'Editar piso' : 'Agregar piso' }}</h2>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="row g-3">
                        <InputText v-model="floorForm.name" title="Nombre" :isRequired="true" :xl="8" :lg="8" :md="12" :sm="12"/>
                        <InputText v-model="floorForm.code" title="Código" :isRequired="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        <InputNumber v-model="floorForm.levelNumber" title="Nivel" :hasNegative="true" :decimals="0" :hasDiv="true" :xl="6" :lg="6" :md="12" :sm="12"/>
                        <InputNumber v-model="floorForm.sortOrder" title="Orden" :decimals="0" :hasDiv="true" :xl="6" :lg="6" :md="12" :sm="12"/>
                        <div class="form-group col-md-6">
                            <label class="form-label">Fondo del plano</label>
                            <div class="br-service-color-options">
                                <button
                                    v-for="color in floorBackgroundColors"
                                    :key="color"
                                    type="button"
                                    :class="{'is-selected': floorForm.backgroundColor === color}"
                                    :style="{backgroundColor: color}"
                                    :aria-label="`Usar fondo ${color}`"
                                    @click="floorForm.backgroundColor = color"></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="saving" @click="saveFloor">
                        {{ editingFloorId ? 'Editar piso' : 'Agregar piso' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="brServiceStationModal" class="modal fade br-entity-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Restaurante y servicios</p>
                        <h2 class="modal-title br-entity-modal__title">{{ editingStationId ? 'Editar mesa' : 'Agregar mesa' }}</h2>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="row g-3">
                        <div class="form-group col-12">
                            <label class="form-label">Piso</label>
                            <v-select v-model="stationForm.floor" :options="floorOptions" :clearable="false" :searchable="false"/>
                        </div>
                        <InputText v-model="stationForm.name" title="Nombre" :isRequired="true" :xl="8" :lg="8" :md="12" :sm="12"/>
                        <InputText v-model="stationForm.code" title="Código" :isRequired="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        <div class="form-group col-md-8">
                            <label class="form-label">Tipo</label>
                            <v-select v-model="stationForm.type" :options="stationTypeOptions" :clearable="false"/>
                        </div>
                        <InputNumber v-model="stationForm.capacity" title="Capacidad" :minValue="1" :decimals="0" :hasDiv="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        <div class="form-group col-md-8">
                            <label class="form-label">Color de referencia</label>
                            <div class="br-service-color-options">
                                <button
                                    v-for="color in stationColorOptions"
                                    :key="color"
                                    type="button"
                                    :class="{'is-selected': stationForm.color === color}"
                                    :style="{backgroundColor: color}"
                                    :aria-label="`Usar color ${color}`"
                                    @click="stationForm.color = color"></button>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-label">Forma</label>
                            <v-select v-model="stationForm.shape" :options="stationShapeOptions" :clearable="false" :searchable="false"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="saving" @click="saveStation">
                        {{ editingStationId ? 'Editar mesa' : 'Agregar mesa' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <FormModal
        modal-id="brServiceSessionModal"
        :title="isRestaurant ? 'ABRIR MESA' : 'INICIAR ATENCIÓN'"
        modal-size="modal-lg"
        :modal-class="isRestaurant ? '' : 'br-entity-modal--tabbed'"
        :submit-disabled="saving || !selectedBranch"
        :submit-button-text="isRestaurant ? 'Abrir mesa' : 'Guardar atención'"
        @submit="saveSession">
        <template #body>
            <CatalogFormTabs
                v-if="!isRestaurant"
                v-model="sessionFormTab"
                :tabs="sessionFormTabs"
                id-prefix="service-session"
                controls-prefix="service-session"
                variant="descriptive"
                label="Secciones de la atención"/>
            <section
                v-show="isRestaurant || sessionFormTab === 'attention'"
                :id="isRestaurant ? undefined : 'service-session-tab-attention'"
                :aria-labelledby="isRestaurant ? undefined : 'service-session-tab-trigger-attention'"
                :role="isRestaurant ? undefined : 'tabpanel'"
                class="br-entity-form-section">
                <div class="row g-3">
                        <div v-if="sessionForm.station" class="col-12">
                            <div class="br-service-selected-station">
                                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                                <span><small>Mesa</small><strong>{{ sessionForm.station.name }}</strong></span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label colon-at-end">Cliente</label>
                            <v-select v-model="sessionForm.customer" :options="customerOptions" :clearable="true" :searchable="true" :filterable="false" @search="searchCustomers" placeholder="Cliente general"/>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label colon-at-end">Responsable</label>
                            <v-select v-model="sessionForm.user" :options="userOptions" :clearable="true" :searchable="true" placeholder="Sin asignar"/>
                        </div>
                        <div class="form-group" :class="sessionForm.item ? 'col-md-8' : 'col-12'">
                            <label class="form-label colon-at-end">{{ isRestaurant ? 'Primer producto o servicio' : 'Servicio inicial' }}</label>
                            <v-select v-model="sessionForm.item" :options="itemOptions" :clearable="true" :searchable="true" :filterable="false" @search="searchItems" placeholder="Opcional · puedes agregarlo después"/>
                        </div>
                        <InputNumber v-if="sessionForm.item" v-model="sessionForm.quantity" title="Cantidad" :minValue="0.0001" :decimals="4" :hasDiv="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        <div class="col-12">
                            <label class="br-entity-switch" for="startServiceImmediately">
                                <input id="startServiceImmediately" v-model="sessionForm.startImmediately" class="form-check-input" type="checkbox" role="switch">
                                <span>
                                    <strong>Iniciar ahora</strong>
                                    <small>Comienza a medir el tiempo desde que confirmes la atención.</small>
                                </span>
                            </label>
                        </div>
                </div>
            </section>
            <section
                v-if="!isRestaurant"
                v-show="sessionFormTab === 'schedule'"
                id="service-session-tab-schedule"
                aria-labelledby="service-session-tab-trigger-schedule"
                class="br-entity-form-section"
                role="tabpanel">
                <div class="row g-3">
                    <div class="form-group col-md-6">
                        <label class="form-label colon-at-end">Fecha programada</label>
                        <input v-model="sessionForm.scheduledAt" type="datetime-local" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="form-label colon-at-end">Fin esperado</label>
                        <input v-model="sessionForm.expectedEndAt" type="datetime-local" class="form-control">
                    </div>
                    <InputNumber v-model="sessionForm.toleranceMinutes" title="Tolerancia (min)" :decimals="0" :hasDiv="true" :xl="6" :lg="6" :md="12" :sm="12"/>
                    <InputText v-model="sessionForm.queueCode" title="Código de turno" :xl="6" :lg="6" :md="12" :sm="12"/>
                </div>
            </section>
        </template>
    </FormModal>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import CatalogFormTabs from "@System/Components/Catalogs/CatalogFormTabs.vue";
import CopyButton from "@System/Components/CopyButton.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";
import TableActionsDropdown from "@System/Components/Generics/TableActionsDropdown.vue";

const SESSION_FORM_TABS = [
    {id: "attention", label: "ATENCIÓN", description: "Cliente y servicio", icon: "fa-solid fa-clipboard-list"},
    {id: "schedule", label: "PROGRAMACIÓN", description: "Fechas y turno", icon: "fa-solid fa-calendar-days"}
];

const SESSION_STATUS_VARIANTS = {pending: "warning", in_progress: "primary", completed: "success", canceled: "danger"};

export default {
    components: {CatalogFormTabs, CopyButton, FormModal, StatusBadge, TableActionsDropdown},
    data() {
        const isRestaurant = window.location.pathname.includes("/restaurant");

        return {
            isRestaurant,
            config: Requests.config({entity: "service_operations"}),
            options: {
                branches: [],
                users: [],
                customers: [],
                items: [],
                stationTypes: [],
                stationColors: [],
                stationShapes: [],
                sessionStatuses: []
            },
            selectedBranch: null,
            selectedStatus: null,
            floors: [],
            selectedFloor: null,
            stations: [],
            sessions: {data: [], links: []},
            reports: null,
            selectedSession: null,
            sessionFormTab: SESSION_FORM_TABS[0].id,
            loading: false,
            sessionsRequestSequence: 0,
            reportsRequestSequence: 0,
            optionSearchTimers: {customers: null, items: null},
            optionSearchSequence: {customers: 0, items: 0},
            saving: false,
            now: Date.now(),
            timer: null,
            dragState: null,
            dragHandlers: null,
            editingFloorId: null,
            editingStationId: null,
            floorForm: {name: "", code: "", levelNumber: 1, sortOrder: 1, backgroundColor: "#f7f8fa"},
            stationForm: {floor: null, name: "", code: "", type: null, capacity: 2, color: "#2899e5", shape: null},
            sessionForm: {
                station: null,
                customer: null,
                user: null,
                item: null,
                quantity: 1,
                startImmediately: true,
                scheduledAt: "",
                expectedEndAt: "",
                toleranceMinutes: 0,
                queueCode: ""
            },
            detailForm: {item: null, user: null},
            operationForm: {user: null, note: ""}
        };
    },
    computed: {
        sessionFormTabs() {
            return SESSION_FORM_TABS;
        },
        sessionStatusVariants() {
            return SESSION_STATUS_VARIANTS;
        },
        breadcrumbTitles() {
            return this.isRestaurant
                ? [{title: "Ventas"}, {title: "Punto de venta"}, {title: "POS restaurante", active: true}]
                : [{title: "Inicio"}, {title: "Seguimiento"}, {title: "Servicios en curso", active: true}];
        },
        branchOptions() {
            return this.options.branches.map(record => ({...record, label: record.name}));
        },
        floorOptions() {
            return this.floors.map(record => ({...record, label: record.name}));
        },
        floorBackgroundColors() {
            return ["#f7f8fa", "#eef6fb", "#f0fdf4", "#fff7ed", "#f5f3ff", "#ffffff"];
        },
        stationColorOptions() {
            return this.options.stationColors?.length
                ? this.options.stationColors
                : ["#2899e5", "#1a1a35", "#10b981", "#d97706", "#dc2626", "#7c3aed"];
        },
        stationShapeOptions() {
            return (this.options.stationShapes || []).map(record => ({...record, label: record.label}));
        },
        userOptions() {
            return this.options.users.map(record => ({...record, label: record.name}));
        },
        customerOptions() {
            return this.options.customers.map(record => ({...record, label: record.name}));
        },
        itemOptions() {
            const records = this.isRestaurant
                ? this.options.items
                : this.options.items.filter(record => record.type === "service");

            return records.map(record => ({
                ...record,
                label: `${record.name} · S/ ${this.money(record.price)}`
            }));
        },
        stationTypeOptions() {
            return this.options.stationTypes.map(record => ({...record, label: record.label}));
        },
        statusOptions() {
            return [
                {code: "open", label: "Pendientes y en curso"},
                ...this.options.sessionStatuses
            ];
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-customers", {addClass: "open"});
        Utils.navbarItem(this.isRestaurant ? "menu-restaurant-pos" : "menu-service-sessions", {addClass: "active"});
        this.timer = window.setInterval(() => { this.now = Date.now(); }, 30000);
        this.initParams();
    },
    beforeUnmount() {
        window.clearInterval(this.timer);
        window.clearTimeout(this.optionSearchTimers.customers);
        window.clearTimeout(this.optionSearchTimers.items);
        this.removeStationDragListeners();
    },
    methods: {
        async initParams() {
            this.loading = true;
            const result = await Requests.get({
                route: this.config.routes.initParams,
                data: {page: this.isRestaurant ? "restaurant" : "services"}
            });
            this.loading = false;

            if(!Requests.valid({result})) {
                this.notify(result, "No fue posible cargar la operación.");
                return;
            }

            this.options = result.data.config;
            this.selectedBranch = this.branchOptions[0] || null;
            this.selectedStatus = this.statusOptions[0] || null;
            this.stationForm.type = this.stationTypeOptions.find(type => type.code === "table") || this.stationTypeOptions[0] || null;
            this.stationForm.shape = this.stationShapeOptions.find(shape => shape.code === "round") || this.stationShapeOptions[0] || null;
            await this.refresh();
            this.$nextTick(() => this.preloadOperationOptions());
        },
        async refresh() {
            if(!this.selectedBranch) return;
            if(this.isRestaurant) {
                await this.getBoard();
                return;
            }

            await Promise.all([this.getSessions(), this.getReports()]);
        },
        async handleBranchChange() {
            this.selectedSession = null;
            this.selectedFloor = null;
            this.reports = null;
            this.sessions = {data: [], links: [], total: 0};
            await this.refresh();
        },
        async getFloors() {
            const result = await Requests.get({route: this.config.routes.floors, data: {branch_id: this.selectedBranch.id}});
            if(!Requests.valid({result})) {
                this.floors = [];
                this.selectedFloor = null;
                return;
            }

            this.floors = result.data.data;
            this.selectedFloor = this.floors.find(floor => floor.id === this.selectedFloor?.id) || this.floors[0] || null;
        },
        async getBoard() {
            this.loading = true;
            const result = await Requests.get({
                route: this.config.routes.board,
                data: {
                    branch_id: this.selectedBranch.id,
                    service_floor_id: this.selectedFloor?.id || null
                }
            });
            this.loading = false;

            if(!Requests.valid({result})) {
                this.floors = [];
                this.selectedFloor = null;
                this.stations = [];
                this.notify(result, "No fue posible cargar las mesas.");
                return;
            }

            const data = result.data.data;
            this.floors = data.floors || [];
            this.selectedFloor = this.floors.find(floor => floor.id === data.selected_floor_id) || null;
            this.stations = data.stations || [];
        },
        async getStations() {
            if(!this.selectedFloor) {
                this.stations = [];
                return;
            }

            this.loading = true;
            const result = await Requests.get({
                route: this.config.routes.stations,
                data: {branch_id: this.selectedBranch.id, service_floor_id: this.selectedFloor.id}
            });
            this.loading = false;
            if(Requests.valid({result})) this.stations = result.data.data;
        },
        async selectFloor(floor) {
            this.selectedFloor = floor;
            this.selectedSession = null;
            await this.getStations();
        },
        preloadOperationOptions() {
            this.loadOperationOptions("customers", "");
            this.loadOperationOptions("items", "");
        },
        searchCustomers(search, toggleLoading) {
            this.scheduleOptionSearch("customers", search, toggleLoading);
        },
        searchItems(search, toggleLoading) {
            this.scheduleOptionSearch("items", search, toggleLoading);
        },
        scheduleOptionSearch(resource, search, toggleLoading) {
            window.clearTimeout(this.optionSearchTimers[resource]);
            toggleLoading?.(true);
            this.optionSearchTimers[resource] = window.setTimeout(async () => {
                await this.loadOperationOptions(resource, search);
                toggleLoading?.(false);
            }, 250);
        },
        async loadOperationOptions(resource, search) {
            const sequence = ++this.optionSearchSequence[resource];
            const result = await Requests.get({
                route: this.config.routes.options,
                data: {
                    resource,
                    search: search || "",
                    item_type: resource === "items" && !this.isRestaurant ? "service" : null
                }
            });

            if(sequence !== this.optionSearchSequence[resource] || !Requests.valid({result})) return;

            this.options[resource] = result.data.data || [];
        },
        async getSessions(url = null) {
            if(!this.selectedBranch) return;

            const sequence = ++this.sessionsRequestSequence;
            this.loading = true;
            const status = this.selectedStatus?.code;
            const result = await Requests.get({
                route: url || this.config.routes.sessions,
                data: {
                    branch_id: this.selectedBranch?.id,
                    status,
                    session_type: "catalog_service"
                }
            });
            if(sequence !== this.sessionsRequestSequence) return;

            this.loading = false;

            if(Requests.valid({result})) {
                this.sessions = result.data.data;
            } else {
                this.sessions = {data: [], links: [], total: 0};
                this.notify(result, "No fue posible cargar las atenciones.");
            }

            this.$nextTick(() => Alerts.tooltips({}));
        },
        async getReports() {
            if(this.isRestaurant || !this.selectedBranch) return;

            const sequence = ++this.reportsRequestSequence;
            const result = await Requests.get({
                route: this.config.routes.reports,
                data: {
                    branch_id: this.selectedBranch.id,
                    session_type: "catalog_service"
                }
            });

            if(sequence !== this.reportsRequestSequence) return;

            if(Requests.valid({result})) {
                this.reports = result.data.data;
            }
        },
        openStationModal(station = null) {
            this.editingStationId = station?.id || null;

            if(station) {
                this.stationForm = {
                    floor: this.floorOptions.find(floor => floor.id === station.service_floor_id) || this.floorOptions[0] || null,
                    name: station.name,
                    code: station.code,
                    type: this.stationTypeOptions.find(type => type.code === station.station_type) || this.stationTypeOptions[0] || null,
                    capacity: station.capacity,
                    color: station.color || this.stationColorOptions[0],
                    shape: this.stationShapeOptions.find(shape => shape.code === station.shape) || this.stationShapeOptions[0] || null,
                    positionX: station.position_x,
                    positionY: station.position_y
                };
                Alerts.modals({type: "show", id: "brServiceStationModal"});
                return;
            }

            const sequence = this.stations.length + 1;
            const floorCode = this.selectedFloor?.code || "P";
            this.stationForm = {
                floor: this.floorOptions.find(floor => floor.id === this.selectedFloor?.id) || this.floorOptions[0] || null,
                name: `Mesa ${String(sequence).padStart(2, "0")}`,
                code: `${floorCode}-M${String(sequence).padStart(2, "0")}`,
                type: this.stationTypeOptions.find(type => type.code === "table") || this.stationTypeOptions[0] || null,
                capacity: 2,
                color: this.stationColorOptions[0],
                shape: this.stationShapeOptions.find(shape => shape.code === "round") || this.stationShapeOptions[0] || null,
                positionX: null,
                positionY: null
            };
            Alerts.modals({type: "show", id: "brServiceStationModal"});
        },
        openFloorModal(floor = null) {
            this.editingFloorId = floor?.id || null;

            if(floor) {
                this.floorForm = {
                    name: floor.name,
                    code: floor.code,
                    levelNumber: floor.level_number,
                    sortOrder: floor.sort_order,
                    backgroundColor: floor.background_color || "#f7f8fa"
                };
                Alerts.modals({type: "show", id: "brServiceFloorModal"});
                return;
            }

            const sequence = this.floors.length + 1;
            this.floorForm = {
                name: `Piso ${sequence}`,
                code: `P${String(sequence).padStart(2, "0")}`,
                levelNumber: sequence,
                sortOrder: sequence,
                backgroundColor: "#f7f8fa"
            };
            Alerts.modals({type: "show", id: "brServiceFloorModal"});
        },
        async saveFloor() {
            if(!this.floorForm.name?.trim() || !this.floorForm.code?.trim()) {
                Alerts.toastrs({type: "warning", subtitle: "Completa el nombre y código del piso."});
                return;
            }

            this.saving = true;
            Alerts.loading({message: this.editingFloorId ? "Actualizando piso" : "Registrando piso"});
            const result = await Requests[this.editingFloorId ? "patch" : "post"]({
                route: this.editingFloorId ? `${this.config.routes.floors}/${this.editingFloorId}` : this.config.routes.floors,
                data: {
                    branch_id: this.selectedBranch.id,
                    name: this.floorForm.name,
                    code: this.floorForm.code,
                    level_number: this.floorForm.levelNumber,
                    sort_order: this.floorForm.sortOrder || this.floors.length + 1,
                    background_color: this.floorForm.backgroundColor,
                    status: "active"
                }
            });
            this.saving = false;
            Alerts.close();

            if(!Requests.valid({result})) {
                this.notify(result, "No fue posible registrar el piso.");
                return;
            }

            Alerts.modals({type: "hide", id: "brServiceFloorModal"});
            await this.getFloors();
            this.selectedFloor = this.floors.find(floor => floor.id === result.data.data?.id) || this.selectedFloor;
            await this.getStations();
            this.editingFloorId = null;
            this.notify(result, "Piso guardado correctamente.", "success");
        },
        openSessionModal(station = null) {
            this.sessionFormTab = SESSION_FORM_TABS[0].id;
            this.sessionForm = {
                station,
                customer: null,
                user: null,
                item: null,
                quantity: 1,
                startImmediately: true,
                scheduledAt: "",
                expectedEndAt: "",
                toleranceMinutes: 0,
                queueCode: ""
            };
            Alerts.modals({type: "show", id: "brServiceSessionModal"});
        },
        async saveStation() {
            if(!this.stationForm.name?.trim() || !this.stationForm.code?.trim()) {
                Alerts.toastrs({type: "warning", subtitle: "Completa el nombre y código de la mesa."});
                return;
            }

            await this.perform({
                message: this.editingStationId ? "Actualizando mesa" : "Registrando mesa",
                request: () => Requests[this.editingStationId ? "patch" : "post"]({
                    route: this.editingStationId ? `${this.config.routes.stations}/${this.editingStationId}` : this.config.routes.stations,
                    data: {
                        branch_id: this.selectedBranch.id,
                        service_floor_id: this.stationForm.floor?.id,
                        name: this.stationForm.name,
                        code: this.stationForm.code,
                        station_type: this.stationForm.type?.code,
                        capacity: this.stationForm.capacity,
                        position_x: this.stationForm.positionX,
                        position_y: this.stationForm.positionY,
                        color: this.stationForm.color,
                        shape: this.stationForm.shape?.code,
                        status: "active"
                    }
                }),
                modalId: "brServiceStationModal",
                afterSuccess: () => { this.editingStationId = null; }
            });
        },
        async saveSession() {
            await this.perform({
                message: this.isRestaurant ? "Abriendo mesa" : "Creando atención",
                request: () => Requests.post({
                    route: this.config.routes.sessions,
                    data: {
                        branch_id: this.selectedBranch.id,
                        service_station_id: this.sessionForm.station?.id,
                        customer_id: this.sessionForm.customer?.id,
                        assigned_user_id: this.sessionForm.user?.id,
                        item_id: this.sessionForm.item?.id,
                        quantity: this.sessionForm.quantity,
                        session_type: this.isRestaurant ? "restaurant" : "catalog_service",
                        start_immediately: this.sessionForm.startImmediately,
                        scheduled_at: this.sessionForm.scheduledAt || null,
                        expected_end_at: this.sessionForm.expectedEndAt || null,
                        tolerance_minutes: this.sessionForm.toleranceMinutes || 0,
                        queue_code: this.sessionForm.queueCode || null
                    }
                }),
                modalId: "brServiceSessionModal",
                selectResult: true
            });
        },
        async selectSession(session) {
            const result = await Requests.get({route: `${this.config.routes.sessions}/${session.id}`});
            if(Requests.valid({result})) {
                this.selectedSession = result.data.data;
                this.detailForm = {item: null, user: this.userOptions.find(user => user.id === this.selectedSession.assigned_user_id) || null};
                this.operationForm = {
                    user: this.userOptions.find(user => user.id === this.selectedSession.assigned_user_id) || null,
                    note: ""
                };
            }
        },
        async addSessionItem() {
            const sessionId = this.selectedSession.id;
            const saved = await this.perform({
                message: "Agregando detalle",
                request: () => Requests.post({
                    route: `${this.config.routes.sessions}/${sessionId}/items`,
                    data: {
                        item_id: this.detailForm.item?.id,
                        assigned_user_id: this.detailForm.user?.id,
                        quantity: 1,
                        start_immediately: this.selectedSession.status === "in_progress"
                    }
                }),
                sessionId
            });
            if(saved) this.detailForm.item = null;
        },
        async changeSessionStatus(action) {
            const sessionId = this.selectedSession.id;
            await this.perform({
                message: action === "start" ? "Iniciando atención" : "Finalizando atención",
                request: () => Requests.patch({route: `${this.config.routes.sessions}/${sessionId}/${action}`}),
                sessionId
            });
        },
        async changeItemStatus(item, action) {
            await this.perform({
                message: action === "start" ? "Iniciando servicio" : "Finalizando servicio",
                request: () => Requests.patch({route: `${this.config.routes.consult}/items/${item.id}/${action}`}),
                sessionId: this.selectedSession.id
            });
        },
        async changePreparationStatus(item, status) {
            await this.perform({
                message: "Actualizando preparación",
                request: () => Requests.patch({
                    route: `${this.config.routes.consult}/items/${item.id}/preparation-status`,
                    data: {status}
                }),
                sessionId: this.selectedSession.id
            });
        },
        async reassignSession() {
            const sessionId = this.selectedSession.id;
            await this.perform({
                message: "Reasignando responsable",
                request: () => Requests.patch({
                    route: `${this.config.routes.sessions}/${sessionId}/reassign`,
                    data: {
                        assigned_user_id: this.operationForm.user?.id,
                        note: this.operationForm.note || null
                    }
                }),
                sessionId,
                afterSuccess: () => { this.operationForm = {user: null, note: ""}; }
            });
        },
        async pauseSession() {
            const sessionId = this.selectedSession.id;
            await this.perform({
                message: "Registrando pausa",
                request: () => Requests.post({
                    route: `${this.config.routes.sessions}/${sessionId}/pause`,
                    data: {reason: this.operationForm.note || null}
                }),
                sessionId
            });
        },
        async resumeSession() {
            const sessionId = this.selectedSession.id;
            await this.perform({
                message: "Reanudando atención",
                request: () => Requests.patch({route: `${this.config.routes.sessions}/${sessionId}/resume`}),
                sessionId,
                afterSuccess: () => { this.operationForm.note = ""; }
            });
        },
        async cancelSession() {
            const sessionId = this.selectedSession.id;
            await this.perform({
                message: "Cancelando atención",
                request: () => Requests.patch({
                    route: `${this.config.routes.sessions}/${sessionId}/cancel`,
                    data: {reason: this.operationForm.note}
                }),
                sessionId,
                afterSuccess: () => { this.operationForm = {user: null, note: ""}; }
            });
        },
        async perform({message, request, modalId = null, sessionId = null, selectResult = false, afterSuccess = null}) {
            if(this.saving) return false;

            this.saving = true;
            Alerts.loading?.({message});
            const result = await request();
            this.saving = false;
            Alerts.close?.();
            window.Swal?.close?.();

            if(!Requests.valid({result})) {
                this.notify(result, "No fue posible completar la acción.");
                return false;
            }

            if(modalId) Alerts.modals({type: "hide", id: modalId});
            this.notify(result, "Operación completada correctamente.", "success");
            if(typeof afterSuccess === "function") afterSuccess(result);
            await this.refresh();

            const targetId = sessionId || (selectResult ? result.data.data?.id : null);
            if(targetId) await this.selectSession({id: targetId});

            return true;
        },
        stationPositionStyle(station) {
            return {
                left: `${Number(station.position_x || 10)}%`,
                top: `${Number(station.position_y || 15)}%`,
                "--br-station-color": station.color || "#2899e5"
            };
        },
        startStationDrag(event, station) {
            const plan = this.$refs.floorPlan;
            if(!plan) return;

            this.removeStationDragListeners();
            this.dragState = {station, rect: plan.getBoundingClientRect()};
            this.dragHandlers = {
                move: pointerEvent => this.moveStationDrag(pointerEvent),
                end: () => this.endStationDrag()
            };
            window.addEventListener("pointermove", this.dragHandlers.move, {passive: false});
            window.addEventListener("pointerup", this.dragHandlers.end, {once: true});
            event.currentTarget?.setPointerCapture?.(event.pointerId);
        },
        moveStationDrag(event) {
            if(!this.dragState) return;
            event.preventDefault();

            const {rect, station} = this.dragState;
            station.position_x = Math.min(95, Math.max(5, ((event.clientX - rect.left) / rect.width) * 100));
            station.position_y = Math.min(95, Math.max(5, ((event.clientY - rect.top) / rect.height) * 100));
        },
        async endStationDrag() {
            const station = this.dragState?.station;
            this.removeStationDragListeners();
            if(station) await this.saveStationLayout(station);
        },
        removeStationDragListeners() {
            if(this.dragHandlers) {
                window.removeEventListener("pointermove", this.dragHandlers.move);
                window.removeEventListener("pointerup", this.dragHandlers.end);
            }
            this.dragHandlers = null;
            this.dragState = null;
        },
        async cycleStationColor(station) {
            const currentIndex = this.stationColorOptions.indexOf(station.color);
            station.color = this.stationColorOptions[(currentIndex + 1) % this.stationColorOptions.length];
            await this.saveStationLayout(station);
        },
        async saveStationLayout(station) {
            const result = await Requests.patch({
                route: `${this.config.routes.stations}/${station.id}/layout`,
                data: {
                    service_floor_id: this.selectedFloor?.id,
                    position_x: Utils.fixedNumber(station.position_x || 10),
                    position_y: Utils.fixedNumber(station.position_y || 15),
                    color: station.color,
                    shape: station.shape || "round"
                }
            });

            if(!Requests.valid({result})) {
                this.notify(result, "No fue posible guardar la distribución de la mesa.");
                await this.getStations();
            }
        },
        notify(result, fallback, type = "error") {
            Alerts.toastrs({type, subtitle: result?.data?.msg || fallback});
        },
        goToPos() {
            window.location.href = `/sales/pos?service_session_id=${this.selectedSession.id}`;
        },
        stationClass(station) {
            if(!station.active_session) return "is-available";
            return station.active_session.status === "in_progress" ? "is-progress" : "is-pending";
        },
        statusLabel(status) {
            return {pending: "Pendiente", in_progress: "En curso", completed: "Finalizada", canceled: "Cancelada"}[status] || status;
        },
        preparationStatusLabel(status) {
            return {pending: "Pendiente", preparing: "Preparando", ready: "Listo", delivered: "Entregado"}[status || "pending"] || status;
        },
        preparationStatusClass(status) {
            return {
                pending: "br-status-pending",
                preparing: "br-status-active",
                ready: "br-status-completed",
                delivered: "br-status-completed"
            }[status || "pending"];
        },
        nextPreparationAction(item) {
            return {
                pending: {status: "preparing", label: "Preparar"},
                preparing: {status: "ready", label: "Marcar listo"},
                ready: {status: "delivered", label: "Entregar"}
            }[item.preparation_status || "pending"] || null;
        },
        isSessionPaused(session) {
            const latestPauseEvent = session.events
                ?.filter(event => ["paused", "resumed"].includes(event.event_type))
                .sort((left, right) => right.id - left.id)[0];

            return latestPauseEvent?.event_type === "paused";
        },
        eventLabel(event) {
            const labels = {
                opened: "Atención creada",
                started: "Atención iniciada",
                completed: "Atención finalizada",
                canceled: "Atención cancelada",
                reassigned: "Responsable reasignado",
                paused: "Atención pausada",
                resumed: "Atención reanudada",
                item_added: "Detalle agregado",
                item_started: "Detalle iniciado",
                item_completed: "Detalle finalizado",
                preparation_status_changed: `Preparación: ${this.preparationStatusLabel(event.new_status)}`
            };

            return labels[event.event_type] || event.event_type;
        },
        statusClass(status) {
            return {
                pending: "br-status-pending",
                in_progress: "br-status-active",
                completed: "br-status-completed",
                canceled: "br-status-inactive"
            }[status];
        },
        elapsedLabel(record) {
            if(record.status === "canceled" && !record.started_at) return "Sin iniciar";
            if(["completed", "canceled"].includes(record.status)) return this.minutesLabel(record.duration_minutes);
            if(record.status === "pending" && !record.started_at) return "Sin iniciar";

            const start = record.started_at;
            if(!start) return "Sin iniciar";
            return this.minutesLabel(Math.max(0, Math.floor((this.now - new Date(start).getTime()) / 60000)));
        },
        minutesLabel(minutes) {
            const value = Number(minutes || 0);
            const hours = Math.floor(value / 60);
            const remaining = value % 60;
            return hours ? `${hours} h ${remaining} min` : `${remaining} min`;
        },
        formatDateTime(value) {
            return value ? new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value)) : "-";
        },
        money(value) {
            return Utils.separatorNumber(value || 0);
        }
    }
};
</script>
