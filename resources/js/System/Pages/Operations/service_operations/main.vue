<template>
    <FgPageHeader v-if="isRestaurant" title="POS restaurante" description="Consulta las mesas y gestiona el servicio por piso."/>
    <Breadcrumb v-else :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" :table="!isRestaurant"/>
        <div v-else>
            <div class="fg-service-page" :class="{'fg-service-page--sessions': !isRestaurant}" :inert="Boolean(selectedSession)">
                <div class="fg-service-toolbar" :class="{'fg-service-toolbar--sessions': !isRestaurant}">
                    <div class="fg-service-toolbar__field">
                        <label class="fg-field__label colon-at-end">Sucursal</label>
                        <FgSelect
                            v-model="selectedBranch"
                            aria-label="Sucursal"
                            :options="branchOptions"
                            :clearable="false"
                            :searchable="true"
                            :disabled="loading"
                            @option:selected="handleBranchChange"/>
                    </div>
                    <div v-if="!isRestaurant" class="fg-service-toolbar__field">
                        <label class="fg-field__label colon-at-end">Estado</label>
                        <FgSelect
                            v-model="selectedStatus"
                            aria-label="Estado de la atención"
                            :options="statusOptions"
                            :clearable="false"
                            :searchable="false"
                            :disabled="loading || !selectedBranch"
                            @option:selected="getSessions()"/>
                    </div>
                    <div class="fg-service-toolbar__actions">
                        <button
                            v-if="isRestaurant"
                            type="button"
                            class="fg-button fg-button--secondary"
                            @click="openFloorModal()">
                            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            <span>Agregar piso</span>
                        </button>
                        <button
                            v-if="isRestaurant"
                            type="button"
                            class="fg-button"
                            :disabled="!selectedFloor"
                            :title="selectedFloor ? 'Agregar mesa al piso actual' : 'Primero agrega un piso'"
                            @click="openStationModal()">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Agregar mesa</span>
                        </button>
                        <button
                            v-else
                            type="button"
                            class="fg-button"
                            :disabled="loading || !selectedBranch"
                            @click="openSessionModal()">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Iniciar atención</span>
                        </button>
                    </div>
                </div>

                <FgListLoading label="Cargando las mesas…" v-if="isRestaurant && loading"/>

                <template v-if="isRestaurant && !loading">
                    <template v-if="floors.length">
                        <div class="fg-service-floor-nav">
                            <div
                                v-for="floor in floors"
                                :key="floor.id"
                                class="fg-service-floor-nav__group">
                                <button
                                    type="button"
                                    class="fg-service-floor-nav__item"
                                    :class="{'is-active': selectedFloor?.id === floor.id}"
                                    @click="selectFloor(floor)">
                                    <span>{{ floor.name }}</span>
                                    <small>{{ floor.stations_count || 0 }}</small>
                                </button>
                                <button
                                    type="button"
                                    class="fg-button fg-button--icon"
                                    data-bs-toggle="tooltip"
                                    title="Editar piso"
                                    aria-label="Editar piso"
                                    @click.stop="openFloorModal(floor)">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="fg-service-floor-meta" :class="{'fg-service-floor-meta--plan': showMobileFloorPlan}">
                            <div class="fg-service-legend" aria-label="Leyenda de disponibilidad">
                                <span><i class="is-available" aria-hidden="true"></i> Disponibles {{ stationCounts.available }}</span>
                                <span><i class="is-pending" aria-hidden="true"></i> Pendientes {{ stationCounts.pending }}</span>
                                <span><i class="is-progress" aria-hidden="true"></i> En atención {{ stationCounts.progress }}</span>
                            </div>
                            <small class="fg-service-floor-hint"><i class="fa-solid fa-arrows-up-down-left-right" aria-hidden="true"></i> Arrastra desde el asa para ordenar el plano.</small>
                            <button type="button" class="fg-button fg-button--secondary fg-service-floor-toggle" :aria-pressed="showMobileFloorPlan" @click="showMobileFloorPlan = !showMobileFloorPlan">
                                {{ showMobileFloorPlan ? "Ver lista" : "Ver plano" }}
                            </button>
                        </div>

                        <div class="fg-service-floor-plan-scroll" :class="{'fg-service-floor-plan-scroll--plan': showMobileFloorPlan}" role="region" aria-label="Mesas del piso" tabindex="0">
                            <div
                                ref="floorPlan"
                                class="fg-service-floor-plan"
                                :style="{backgroundColor: selectedFloor?.background_color || 'var(--fg-table-head)'}">
                            <article
                                v-for="station in stations"
                                :key="station.id"
                                class="fg-service-map-station"
                                :class="[stationClass(station), `is-${station.shape || 'round'}`]"
                                :style="stationPositionStyle(station)">
                                <button
                                    type="button"
                                    class="fg-service-map-station__drag"
                                    title="Mover mesa"
                                    aria-label="Mover mesa"
                                    @pointerdown.prevent="startStationDrag($event, station)">
                                    <i class="fa-solid fa-grip" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="fg-service-map-station__color"
                                    title="Cambiar color"
                                    aria-label="Cambiar color"
                                    @click.stop="cycleStationColor(station)">
                                    <i class="fa-solid fa-palette" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="fg-service-map-station__edit"
                                    title="Editar mesa"
                                    aria-label="Editar mesa"
                                    @click.stop="openStationModal(station)">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="fg-service-map-station__body"
                                    @click="station.active_session ? selectSession(station.active_session) : openSessionModal(station)">
                                    <strong>{{ station.name }}</strong>
                                    <small>{{ station.capacity }} personas</small>
                                    <span class="fg-badge" :class="station.active_session ? (station.active_session.status === 'in_progress' ? 'fg-service-badge--progress' : 'fg-badge--warning') : 'fg-badge--success'">
                                        {{ station.active_session ? (station.active_session.status === "in_progress" ? "En atención" : "Pendiente") : "Disponible" }}
                                    </span>
                                    <small v-if="station.active_session">{{ elapsedLabel(station.active_session) }}</small>
                                </button>
                            </article>

                                <div v-if="!stations.length" class="fg-service-floor-plan__empty">
                                    <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                                    <strong>Este piso aún no tiene mesas</strong>
                                    <span>Agrega una mesa para comenzar a diseñar la distribución.</span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <FgEmptyState
                        v-else
                        title="Aún no hay pisos registrados"
                        description="Crea el primer piso o zona para ubicar las mesas de esta sucursal."/>
                </template>

                <template v-if="!isRestaurant">
                    <div v-if="reports" class="fg-service-report-grid" aria-label="Resumen del mes">
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

                    <section class="fg-service-list" aria-label="Listado de atenciones">
                        <ResponsiveTable class="fg-sales-table-scroll">
                        <table class="fg-service-list__table mb-0 fg-table">
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
                            <thead>
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
                                <tr class="fg-table__state" v-if="loading">
                                    <td colspan="8" class="py-4"><FgListLoading label="Cargando las mesas…"/></td>
                                </tr>
                                <template v-else-if="sessions.data.length">
                                <tr v-for="session in sessions.data" :key="session.id">
                                    <td class="fg-entity-table__identification-cell">
                                        <div class="fg-product-codes">
                                            <div class="fg-product-identity__code">
                                                <span class="fg-product-identity__label colon-at-end">Atención</span>
                                                <span class="fg-copy-value">
                                                    <span class="fg-entity-code">{{ session.reference || '—' }}</span>
                                                    <CopyButton v-if="session.reference" :value="session.reference" label="Número de atención"/>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fg-row-title">{{ session.items?.[0]?.name || 'Sin servicio inicial' }}</span>
                                        <span v-if="session.items?.length > 1" class="fg-table-note">+ {{ session.items.length - 1 }} {{ session.items.length === 2 ? 'servicio' : 'servicios' }}</span>
                                    </td>
                                    <td><span :class="{'fg-service-list__muted': !session.customer}">{{ session.customer?.name || 'Cliente general' }}</span></td>
                                    <td><span :class="{'fg-service-list__muted': !session.assigned_user}">{{ session.assigned_user?.name || 'Sin asignar' }}</span></td>
                                    <td class="fg-service-list__date">{{ formatDateTime(session.started_at || session.created_at) }}</td>
                                    <td class="fg-service-list__time">{{ elapsedLabel(session) }}</td>
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
                                <tr class="fg-table__state" v-else>
                                    <td colspan="8" class="py-4">
                                        <FgEmptyState title="No se encontraron registros" v-if="selectedBranch"/>
                                        <FgEmptyState v-else title="No hay sucursales disponibles" description="Solicita acceso a una sucursal para gestionar atenciones."/>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </ResponsiveTable>
                    </section>
                    <Paginator aria-label="Paginación de atenciones" :links="sessions.links" :pagination="sessions" :loading="loading" @clickPage="getSessions"/>
                </template>

            </div>
                <div v-if="selectedSession" class="fg-service-detail-backdrop" aria-hidden="true" @click="selectedSession = null"></div>
                <aside v-if="selectedSession" ref="sessionDetail" class="fg-service-detail" :class="{'fg-service-detail--sessions': !isRestaurant}" role="dialog" aria-modal="true" aria-labelledby="fg-service-detail-title" @keydown="handleDetailKeydown">
                    <header class="fg-service-detail__header">
                        <div>
                            <small>Atención {{ selectedSession.reference }}</small>
                            <h2 id="fg-service-detail-title" class="fg-title mb-0">{{ selectedSession.station?.name || selectedSession.items?.[0]?.name || 'Atención de servicio' }}</h2>
                            <p>
                                {{ selectedSession.customer?.name || 'Cliente general' }}
                                <span v-if="selectedSession.assigned_user"> · {{ selectedSession.assigned_user.name }}</span>
                            </p>
                        </div>
                        <button ref="closeSessionDetail" type="button" class="fg-button fg-button--icon" aria-label="Cerrar detalle" @click="selectedSession = null">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="fg-service-detail__content">
                    <div class="fg-service-detail__metrics">
                        <span><small>Estado</small><strong>{{ statusLabel(selectedSession.status) }}</strong></span>
                        <span><small>Tiempo</small><strong>{{ elapsedLabel(selectedSession) }}</strong></span>
                        <span><small>{{ isRestaurant ? 'Detalles' : 'Servicios' }}</small><strong>{{ selectedSession.items?.length || 0 }}</strong></span>
                    </div>

                    <section v-if="['pending', 'in_progress'].includes(selectedSession.status)" class="fg-service-detail__section" aria-label="Gestión de la atención">
                        <h3>Gestión de la atención</h3>
                        <div class="fg-service-detail__operations">
                            <div class="fg-service-detail__field">
                                <label class="fg-field__label colon-at-end">Responsable</label>
                                <FgSelect
                                    v-model="operationForm.user"
                                    :options="userOptions"
                                    :clearable="true"
                                    :searchable="true"
                                    placeholder="Sin asignar"/>
                            </div>
                            <button
                                type="button"
                                class="fg-button fg-button--secondary"
                                :disabled="saving || !operationForm.user"
                                @click="reassignSession">
                                Reasignar
                            </button>
                        </div>
                        <div class="fg-service-detail__field">
                            <label class="fg-field__label colon-at-end">Motivo o nota</label>
                            <input
                                v-model.trim="operationForm.note"
                                type="text"
                                class="fg-native-input"
                                maxlength="500"
                                placeholder="Opcional para pausa; obligatorio para cancelar"/>
                        </div>
                        <div class="fg-service-detail__quick-actions">
                            <button
                                v-if="!isSessionPaused(selectedSession)"
                                type="button"
                                class="fg-button"
                                :disabled="saving"
                                @click="pauseSession">
                                Pausar
                            </button>
                            <button
                                v-else
                                type="button"
                                class="fg-button"
                                :disabled="saving"
                                @click="resumeSession">
                                Reanudar
                            </button>
                            <button
                                type="button"
                                class="fg-button fg-button--danger"
                                :disabled="saving || !operationForm.note"
                                @click="cancelSession">
                                Cancelar atención
                            </button>
                        </div>
                    </section>

                    <section class="fg-service-detail__section" aria-label="Servicios de la atención">
                        <h3>{{ isRestaurant ? 'Detalle de la mesa' : 'Servicios de la atención' }}</h3>
                        <div v-if="['pending', 'in_progress'].includes(selectedSession.status)" class="fg-service-add-item">
                            <div class="fg-service-detail__field">
                                <label class="fg-field__label colon-at-end">{{ isRestaurant ? 'Producto o servicio' : 'Servicio' }}</label>
                                <FgSelect
                                    v-model="detailForm.item"
                                    :options="itemOptions"
                                    :clearable="false"
                                    :searchable="true"
                                    :filterable="false"
                                    :loading="remoteOptions.items.loading"
                                    @open="openOptions('items')"
                                    @search="searchOptions('items', $event)"
                                    placeholder="Seleccionar"/>
                            </div>
                            <div class="fg-service-detail__field">
                                <label class="fg-field__label colon-at-end">Responsable</label>
                                <FgSelect
                                    v-model="detailForm.user"
                                    :options="userOptions"
                                    :clearable="true"
                                    :searchable="true"
                                    placeholder="Sin asignar"/>
                            </div>
                            <button
                                type="button"
                                class="fg-button"
                                :disabled="saving || !detailForm.item"
                                @click="addSessionItem">
                                Agregar
                            </button>
                        </div>

                    <div class="fg-service-detail__items">
                        <article v-for="item in selectedSession.items" :key="item.id">
                            <div>
                                <strong>{{ item.name }}</strong>
                                <small>{{ item.assigned_user?.name || 'Sin responsable' }} · {{ elapsedLabel(item) }}</small>
                            </div>
                            <span v-if="isRestaurant" class="fg-badge" :class="preparationStatusClass(item.preparation_status)">
                                {{ preparationStatusLabel(item.preparation_status) }}
                            </span>
                            <span v-else class="fg-badge" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                            <button
                                v-if="isRestaurant && nextPreparationAction(item)"
                                type="button"
                                class="fg-button fg-button--secondary"
                                @click="changePreparationStatus(item, nextPreparationAction(item).status)">
                                {{ nextPreparationAction(item).label }}
                            </button>
                            <span v-else-if="isRestaurant" class="fg-badge fg-badge--success">Entregado</span>
                            <button
                                v-if="item.status === 'pending' && ['pending', 'in_progress'].includes(selectedSession.status)"
                                type="button"
                                class="fg-button fg-button--secondary"
                                @click="changeItemStatus(item, 'start')">
                                Iniciar
                            </button>
                            <button
                                v-else-if="item.status === 'in_progress' && ['pending', 'in_progress'].includes(selectedSession.status)"
                                type="button"
                                class="fg-button"
                                @click="changeItemStatus(item, 'complete')">
                                Finalizar
                            </button>
                        </article>
                        <p v-if="!selectedSession.items?.length" class="fg-service-detail__empty">Aún no hay {{ isRestaurant ? 'productos' : 'servicios' }} en esta atención.</p>
                    </div>
                    </section>

                    <section v-if="selectedSession.events?.length" class="fg-service-timeline" aria-label="Línea de tiempo">
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

                    <footer class="fg-service-detail__footer">
                        <button
                            v-if="selectedSession.status === 'pending'"
                            type="button"
                            class="fg-button"
                            :disabled="saving"
                            @click="changeSessionStatus('start')">
                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                            <span>Iniciar atención</span>
                        </button>
                        <button
                            v-if="selectedSession.items?.length"
                            type="button"
                            class="fg-button"
                            :disabled="saving"
                            @click="goToPos">
                            <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                            <span>Cobrar en POS</span>
                        </button>
                        <button
                            v-if="selectedSession.status === 'in_progress'"
                            type="button"
                            class="fg-button"
                            :disabled="saving"
                            @click="changeSessionStatus('complete')">
                            <span>Finalizar sin venta</span>
                        </button>
                    </footer>
                </aside>

        </div>
    </InitialLoadTransition>
    <div id="fgServiceFloorModal" class="modal fade fg-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content fg-modal__content">
                <div class="modal-header fg-modal__header">
                    <div>
                        <p class="fg-help mb-1">Restaurante POS</p>
                        <h2 class="modal-title fg-modal__title">{{ editingFloorId ? 'Editar piso' : 'Agregar piso' }}</h2>
                    </div>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
                    <div class="row g-3">
                        <div class="fg-field-cell col-12">
                            <InputText v-model="floorForm.name" title="Nombre" :isRequired="true" :xl="8" :lg="8" :md="12" :sm="12"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputText v-model="floorForm.code" title="Código" :isRequired="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputNumber v-model="floorForm.levelNumber" title="Nivel" isRequired :hasNegative="true" :minValue="-20" :maxValue="200" :decimals="0" :hasDiv="true" :xl="6" :lg="6" :md="12" :sm="12"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputNumber v-model="floorForm.sortOrder" title="Orden" :decimals="0" :hasDiv="true" :xl="6" :lg="6" :md="12" :sm="12"/>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="fg-field__label">Fondo del plano</label>
                            <div class="fg-service-color-options">
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
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="saveFloor">
                        {{ editingFloorId ? 'Editar piso' : 'Agregar piso' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="fgServiceStationModal" class="modal fade fg-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content fg-modal__content">
                <div class="modal-header fg-modal__header">
                    <div>
                        <p class="fg-help mb-1">Restaurante y servicios</p>
                        <h2 class="modal-title fg-modal__title">{{ editingStationId ? 'Editar mesa' : 'Agregar mesa' }}</h2>
                    </div>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
                    <div class="row g-3">
                        <div class="form-group col-12">
                            <label class="fg-field__label">Piso</label>
                            <FgSelect v-model="stationForm.floor" :options="floorOptions" :clearable="false" :searchable="false"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputText v-model="stationForm.name" title="Nombre" :isRequired="true" :xl="8" :lg="8" :md="12" :sm="12"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputText v-model="stationForm.code" title="Código" :isRequired="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        </div>
                        <div class="form-group col-md-8">
                            <label class="fg-field__label">Tipo</label>
                            <FgSelect v-model="stationForm.type" :options="stationTypeOptions" :clearable="false"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputNumber v-model="stationForm.capacity" title="Capacidad" :minValue="1" :decimals="0" :hasDiv="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        </div>
                        <div class="form-group col-md-8">
                            <label class="fg-field__label">Color de referencia</label>
                            <div class="fg-service-color-options">
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
                            <label class="fg-field__label">Forma</label>
                            <FgSelect v-model="stationForm.shape" :options="stationShapeOptions" :clearable="false" :searchable="false"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="saveStation">
                        {{ editingStationId ? 'Editar mesa' : 'Agregar mesa' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <FormModal
        modal-id="fgServiceSessionModal"
        :title="isRestaurant ? 'ABRIR MESA' : 'INICIAR ATENCIÓN'"
        modal-size="modal-lg"
        :modal-class="isRestaurant ? '' : 'fg-entity-modal--tabbed'"
        :submit-disabled="saving || !selectedBranch"
        :submit-button-text="isRestaurant ? 'Abrir mesa' : 'Guardar atención'"
        @submit="saveSession">
        <template #body>
            <AppTabs
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
                class="fg-stack">
                <div class="row g-3">
                        <div v-if="sessionForm.station" class="col-12">
                            <div class="fg-service-selected-station">
                                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                                <span><small>Mesa</small><strong>{{ sessionForm.station.name }}</strong></span>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="fg-field__label colon-at-end">Cliente</label>
                            <FgSelect v-model="sessionForm.customer" :options="customerOptions" :clearable="true" :searchable="true" :filterable="false" :loading="remoteOptions.customers.loading" @open="openOptions('customers')" @search="searchOptions('customers', $event)" placeholder="Cliente general"/>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="fg-field__label colon-at-end">Responsable</label>
                            <FgSelect v-model="sessionForm.user" :options="userOptions" :clearable="true" :searchable="true" placeholder="Sin asignar"/>
                        </div>
                        <div class="form-group" :class="sessionForm.item ? 'col-md-8' : 'col-12'">
                            <label class="fg-field__label colon-at-end">{{ isRestaurant ? 'Primer producto o servicio' : 'Servicio inicial' }}</label>
                            <FgSelect v-model="sessionForm.item" :options="itemOptions" :clearable="true" :searchable="true" :filterable="false" :loading="remoteOptions.items.loading" @open="openOptions('items')" @search="searchOptions('items', $event)" placeholder="Opcional · puedes agregarlo después"/>
                        </div>
                        <div class="fg-field-cell col-12">
                            <InputNumber v-if="sessionForm.item" v-model="sessionForm.quantity" title="Cantidad" :minValue="quantityStep" :hasDiv="true" :xl="4" :lg="4" :md="12" :sm="12"/>
                        </div>
                        <div class="col-12">
                            <label class="fg-check" for="startServiceImmediately">
                                <input id="startServiceImmediately" v-model="sessionForm.startImmediately"  type="checkbox" role="switch">
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
                class="fg-stack"
                role="tabpanel">
                <div class="row g-3">
                    <div class="form-group col-md-6">
                        <label class="fg-field__label colon-at-end">Fecha programada</label>
                        <input v-model="sessionForm.scheduledAt" type="datetime-local" class="fg-native-input">
                    </div>
                    <div class="form-group col-md-6">
                        <label class="fg-field__label colon-at-end">Fin esperado</label>
                        <input v-model="sessionForm.expectedEndAt" type="datetime-local" class="fg-native-input">
                    </div>
                    <div class="fg-field-cell col-12">
                        <InputNumber v-model="sessionForm.toleranceMinutes" title="Tolerancia (min)" :decimals="0" :hasDiv="true" :xl="6" :lg="6" :md="12" :sm="12"/>
                    </div>
                    <div class="fg-field-cell col-12">
                        <InputText v-model="sessionForm.queueCode" title="Código de turno" :xl="6" :lg="6" :md="12" :sm="12"/>
                    </div>
                </div>
            </section>
        </template>
    </FormModal>
</template>

<script>
import {navbarItem} from "@System/Layout/Menu.js";
import {createOptionSearch} from "@/Shared/Options.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import AppTabs from "@/Shared/AppTabs.vue";
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
    mixins: [InitialPageMixin, createOptionSearch("customers", "items")],
    components: {AppTabs, CopyButton, FormModal, StatusBadge, TableActionsDropdown},
    data() {
        const isRestaurant = window.location.pathname.includes("/restaurant");

        return {
            isRestaurant,
            optionOrigin: isRestaurant ? "restaurant" : "service-sessions",
            config: Requests.config({entity: "service_operations"}),
            options: {
                branches: [],
                users: [],
                customers: {records: []},
                items: {records: []},
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
            showMobileFloorPlan: false,
            sessionFormTab: SESSION_FORM_TABS[0].id,
            loading: false,
            sessionsRequestSequence: 0,
            reportsRequestSequence: 0,
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
    watch: {
        selectedSession(current, previous) {
            if(current && !previous) {
                this.detailTrigger = document.activeElement;
                this.$nextTick(() => this.$refs.closeSessionDetail?.focus());
            } else if(!current && previous) {
                this.$nextTick(() => this.detailTrigger?.focus());
            }
        }
    },
    computed: {
        stationCounts() {
            const counts = {available: 0, pending: 0, progress: 0};
            this.stations.forEach(station => counts[this.stationClass(station).slice(3)]++);
            return counts;
        },
        quantityStep() {
            return Utils.decimalStep();
        },
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
            return this.options.customers.records.map(record => ({...record, label: record.name}));
        },
        itemOptions() {
            return this.options.items.records.map(record => ({
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
    async mounted() {
        navbarItem(this.isRestaurant ? "menu-parent-sales" : "menu-parent-workspace", {addClass: "open"});
        navbarItem(this.isRestaurant ? "menu-restaurant-pos" : "menu-service-sessions", {addClass: "active"});
        this.timer = window.setInterval(() => { this.now = Date.now(); }, 30000);
        const initialized = await this.loadInitialParams(() => this.initParams());
        if(initialized) {
            await this.refresh();
            this.$nextTick(() => {
                this.openOptions("customers");
                this.openOptions("items");
            });
        }
    },
    beforeUnmount() {
        window.clearInterval(this.timer);


        this.removeStationDragListeners();
    },
    methods: {
        handleDetailKeydown(event) {
            if(event.defaultPrevented || this.$refs.sessionDetail.querySelector(".vs--open")) return;

            if(event.key === "Escape") {
                this.selectedSession = null;
                return;
            }
            if(event.key !== "Tab") return;

            const focusable = [...this.$refs.sessionDetail.querySelectorAll("button:not(:disabled), input:not(:disabled), [href], [tabindex=\"0\"]")]
                .filter(element => element.tabIndex >= 0 && element.getClientRects().length);
            const endpoint = event.shiftKey ? focusable[0] : focusable.at(-1);
            if(endpoint !== document.activeElement) return;

            event.preventDefault();
            (event.shiftKey ? focusable.at(-1) : focusable[0])?.focus();
        },
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

            this.options = {...result.data.config, customers: {records: []}, items: {records: []}};
            this.selectedBranch = this.branchOptions[0] || null;
            this.selectedStatus = this.statusOptions[0] || null;
            this.stationForm.type = this.stationTypeOptions.find(type => type.code === "table") || this.stationTypeOptions[0] || null;
            this.stationForm.shape = this.stationShapeOptions.find(shape => shape.code === "round") || this.stationShapeOptions[0] || null;
            return true;
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
            this.stations = [];
            try {
                const result = await Requests.get({
                    route: this.config.routes.board,
                    data: {
                        branch_id: this.selectedBranch.id,
                        service_floor_id: this.selectedFloor?.id || null
                    }
                });
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
            } finally {
                this.loading = false;
            }
        },
        async getStations() {
            if(!this.selectedFloor) {
                this.stations = [];
                return;
            }

            this.loading = true;
            this.stations = [];
            try {
                const result = await Requests.get({
                    route: this.config.routes.stations,
                    data: {branch_id: this.selectedBranch.id, service_floor_id: this.selectedFloor.id}
                });
                if(Requests.valid({result})) this.stations = result.data.data;
            } finally {
                this.loading = false;
            }
        },
        async selectFloor(floor) {
            this.selectedFloor = floor;
            this.selectedSession = null;
            await this.getStations();
        },
        async getSessions(url = null) {
            url = typeof url === "object" ? url?.url : url;
            if(!this.selectedBranch) return;

            const sequence = ++this.sessionsRequestSequence;
            this.loading = true;

            try {
                this.sessions = Requests.emptyPagination();
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

                if(Requests.valid({result})) {
                    this.sessions = result.data.data;
                } else {
                    this.sessions = {data: [], links: [], total: 0};
                    this.notify(result, "No fue posible cargar las atenciones.");
                }

                this.$nextTick(() => Alerts.tooltips({}));
            } finally {
                if(sequence === this.sessionsRequestSequence) this.loading = false;
            }
        },
        async getReports() {
            if(this.isRestaurant || !this.selectedBranch) return;

            const sequence = ++this.reportsRequestSequence;
            this.reports = null;
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
                Alerts.modals({type: "show", id: "fgServiceStationModal"});
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
            Alerts.modals({type: "show", id: "fgServiceStationModal"});
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
                Alerts.modals({type: "show", id: "fgServiceFloorModal"});
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
            Alerts.modals({type: "show", id: "fgServiceFloorModal"});
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

            Alerts.modals({type: "hide", id: "fgServiceFloorModal"});
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
            Alerts.modals({type: "show", id: "fgServiceSessionModal"});
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
                modalId: "fgServiceStationModal",
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
                modalId: "fgServiceSessionModal",
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
                "--fg-station-x": `${Number(station.position_x || 10)}%`,
                "--fg-station-y": `${Number(station.position_y || 15)}%`,
                "--fg-station-color": station.color || "var(--fg-primary)"
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
            const fieldError = Object.values(result?.errors || {}).flat()[0];
            Alerts.toastrs({type, subtitle: fieldError || result?.data?.msg || fallback});
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
            return {pending: "Por preparar", preparing: "En preparación", ready: "Listo", delivered: "Entregado"}[status || "pending"] || status;
        },
        preparationStatusClass(status) {
            return {
                pending: "fg-badge--warning",
                preparing: "fg-service-badge--progress",
                ready: "fg-badge--success",
                delivered: "fg-badge--success"
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
                pending: "fg-badge--warning",
                in_progress: "fg-badge--success",
                completed: "fg-badge--success",
                canceled: "fg-badge--danger"
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
