<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Content -->
    <div class="d-flex justify-content-end mb-2" v-if="!lists.entity.extras.loading">
        <a href="javascript:void(0)" @click="selectModeEntity('manual', true)" class="fw-bold">
            <i class="fa fa-check-circle"></i>
            <span class="ms-1">Registrar asistencia</span>
        </a>
    </div>
    <div class="row align-items-start g-2 g-md-4 mb-4 mb-md-4">
        <div class="col-xl-9 col-lg-9 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between pt-3 pb-1">
                    <div class="card-title mb-0">
                        <span class="fw-semibold">Asistencias</span>
                    </div>
                    <div v-show="false">
                        <button class="btn btn-xs btn-primary" @click="initChart('default')" type="button">
                            <span>Refrescar</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="defaultChartId" class="chartjs" data-height="150"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between pt-3 pb-1">
                    <div class="card-title mb-0">
                        <span class="fw-semibold">Actividad</span>
                    </div>
                    <div v-show="false">
                        <button class="btn btn-xs btn-primary" @click="initChart('doughnut')" type="button">
                            <span>Refrescar</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="doughnutChartId" class="chartjs" data-height="150"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row align-items-start g-4 mb-3 mb-md-3">
        <template v-if="forms.entity.createUpdate.config.viewFilters">
            <div class="col-lg-9 col-12">
                <div class="row g-3">
                    <InputSlot
                        hasDiv
                        title="Sucursal"
                        :titleClass="[config.forms.classes.title]"
                        xl="12"
                        lg="12">
                        <template v-slot:input>
                            <v-select
                                v-model="lists.entity.filters.branch"
                                :options="branches"
                                :class="config.forms.classes.select2"
                                :clearable="false"
                                :searchable="false"
                                placeholder="Seleccione una sucursal ..."/>
                        </template>
                    </InputSlot>
                    <InputSlot
                        hasDiv
                        title="Cliente"
                        :titleClass="[config.forms.classes.title]"
                        xl="12"
                        lg="12">
                        <template v-slot:input>
                            <v-select
                                v-model="lists.entity.filters.customer"
                                :options="customers"
                                :class="config.forms.classes.select2"
                                :clearable="true"
                                placeholder="Seleccione un cliente ..."/>
                        </template>
                    </InputSlot>
                    <InputDate
                        v-model="lists.entity.filters.start_date"
                        @change="listEntity({})"
                        hasDiv
                        title="Fecha de ingreso"
                        :titleClass="[config.forms.classes.title]"
                        xl="4"
                        lg="4"/>
                </div>
            </div>
            <div class="col-lg-3 col-12">
                <div class="row g-1">
                    <div class="col-xl-12">
                        <label :class="[config.forms.classes.title]">Estado</label>
                    </div>
                    <div class="col-lg-12 col-xl-12">
                        <div class="form-check">
                            <label class="py-1 cursor-pointer">
                                <input :class="['form-check-input', lists.entity.filters.status == '' ? 'bg-secondary border-secondary' : '']" type="radio" value="" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                                <span class="fw-bold text-secondary">Todos los estados</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-lg-12 col-xl-12">
                        <div class="form-check">
                            <label class="py-1 cursor-pointer">
                                <input :class="['form-check-input', lists.entity.filters.status == 'active' ? 'bg-success border-success' : '']" type="radio" value="active" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                                <span class="fw-bold text-success">En curso</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-lg-12 col-xl-12">
                        <div class="form-check">
                            <label class="py-1 cursor-pointer">
                                <input :class="['form-check-input', lists.entity.filters.status == 'finalized' ? 'bg-primary border-primary' : '']" type="radio" value="finalized" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                                <span class="fw-bold text-primary">Concluida</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-lg-12 col-xl-12">
                        <div class="form-check">
                            <label class="py-1 cursor-pointer">
                                <input :class="['form-check-input', lists.entity.filters.status == 'canceled' ? 'bg-danger border-danger' : '']" type="radio" value="canceled" v-model="lists.entity.filters.status" @change="listEntity({})"/>
                                <span class="fw-bold text-danger">Anulada</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <InputSlot
            hasDiv
            :divClass="['mt-3']"
            :isInputGroup="false"
            :divInputClass="['d-flex flex-wrap justify-content-start align-items-center gap-2 gap-md-3']"
            xl="12"
            lg="12">
            <template v-slot:input>
                <template v-if="forms.entity.createUpdate.config.viewFilters">
                    <button type="button" class="br-btn br-btn-search waves-effect" @click="listEntity({})" :disabled="lists.entity.extras.loading">
                        <span>Filtrar</span>
                    </button>
                </template>
                <button type="button" class="btn btn-primary waves-effect" @click="selectModeEntity('manual', true)" :disabled="lists.entity.extras.loading">
                    <i class="fa fa-check"></i>
                    <span class="ms-2">Registrar asistencia</span>
                </button>
                <button type="button" class="btn btn-success waves-effect" @click="modalAutomaticEntity({type: 'generate'})" :disabled="lists.entity.extras.loading">
                    <i class="fa fa-globe"></i>
                    <span class="ms-2">Modo público</span>
                </button>
                <button type="button" class="br-btn br-btn-action-export waves-effect" @click="exportEntity" :disabled="lists.entity.extras.loading" data-bs-toggle="tooltip" data-bs-placement="top" title="Exportar asistencias">
                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                    <span class="ms-2 d-inline d-md-none">Exportar</span>
                </button>
                <a href="javascript:void(0)" class="fw-bold ms-2" @click="forms.entity.createUpdate.config.viewFilters = !forms.entity.createUpdate.config.viewFilters;">
                    <i :class="['fa', forms.entity.createUpdate.config.viewFilters ? 'fa-eye-slash' : 'fa-eye']"></i>
                    <span class="ms-2" v-text="forms.entity.createUpdate.config.viewFilters ? 'Ocultar filtros' : 'Mostrar filtros'"></span>
                </a>
            </template>
        </InputSlot>
    </div>
    <div class="d-flex justify-content-end" v-if="lists.entity.records.total > 0">
        <span class="colon-at-end">Sucursal</span>
        <span v-text="lists.entity.records.data[0]?.branch?.name" class="ms-1 fw-bold"></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="text-center align-middle">
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 25%;"></th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 45%;">CLIENTE</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 15%;">INGRESO</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 15%;">SALIDA</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <template v-if="lists.entity.extras.loading">
                    <tr class="text-center">
                        <td colspan="99" class="py-4">
                            <Loader/>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <template v-if="lists.entity.records.total > 0">
                        <tr v-for="record in lists.entity.records.data" :key="record.id" class="text-center">
                            <td :class="[{ 'br-status-surface--success': ['active'].includes(record.status), 'br-status-surface--primary': ['finalized'].includes(record.status), 'br-status-surface--danger': ['canceled'].includes(record.status), 'br-status-surface--warning': ['inactive'].includes(record.status) }]">
                                <span class="d-block fw-bold small" v-text="record.formatted_status"></span>
                                <InputSlot
                                    v-if="['active', 'finalized'].includes(record?.status)"
                                    :hasDiv="false"
                                    :isInputGroup="false"
                                    :divInputClass="['d-flex flex-wrap justify-content-center gap-2 gap-md-2 mt-1']">
                                    <template v-slot:input>
                                        <button v-if="['active'].includes(record?.status)" type="button" class="btn btn-xs btn-warning waves-effect" @click="modalCreateUpdateEntity({record, type: 'finalized'})">
                                            <i class="fa-solid fa-person-walking-arrow-right"></i>
                                            <span class="ms-2">Concluir</span>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger waves-effect" @click="modalCreateUpdateEntity({record, type: 'canceled'})">
                                            <i class="fa fa-times"></i>
                                            <span class="ms-2">Anular</span>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-info waves-effect" @click="requestCorrectionEntity(record)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="ms-2">Corregir</span>
                                        </button>
                                    </template>
                                </InputSlot>
                            </td>
                            <td class="text-start">
                                <span v-text="record.customer?.name" class="fw-bold d-block"></span>
                                <small v-text="record.customer?.document_number" class="d-block"></small>
                                <small class="br-attendance-device-detail">
                                    <i class="fa-solid fa-fingerprint" aria-hidden="true"></i>
                                    <span v-text="getAttendanceDeviceLabel(record)"></span>
                                </small>
                                <small v-if="latestAttendanceCorrection(record)" class="br-attendance-device-detail is-warning">
                                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                                    <span v-text="latestAttendanceCorrectionLabel(record)"></span>
                                </small>
                            </td>
                            <td>
                                <template v-if="isDefined({value: record.start_date})">
                                    <span v-text="legibleFormatDate({dateString: record.start_date, type: 'date'})" class="d-block fw-semibold"></span>
                                    <span v-text="legibleFormatDate({dateString: record.start_date, type: 'time'})" class="d-block fw-semibold"></span>
                                </template>
                                <span v-else class="d-block fw-bold text-dark">Pendiente</span>
                            </td>
                            <td :class="[{ 'br-status-surface--secondary': ['active'].includes(record.status) }]">
                                <template v-if="isDefined({value: record.end_date})">
                                    <span v-text="legibleFormatDate({dateString: record.end_date, type: 'date'})" class="d-block fw-semibold"></span>
                                    <span v-text="legibleFormatDate({dateString: record.end_date, type: 'time'})" class="d-block fw-semibold"></span>
                                </template>
                                <span v-else class="d-block fw-bold text-dark">Pendiente</span>
                            </td>
                        </tr>
                    </template>
                    <template v-else>
                        <tr>
                            <td class="text-center" colspan="99">
                                <WithoutData type="image"/>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end mt-2" v-if="!lists.entity.extras.loading">
        <a href="javascript:void(0)" @click="selectModeEntity('manual', true)" class="fw-bold">
            <i class="fa fa-check-circle"></i>
            <span class="ms-1">Registrar asistencia</span>
        </a>
    </div>
    <div class="d-flex justify-content-center d-none" v-if="!lists.entity.extras.loading && lists.entity.records?.total > 0">
        <Paginator :links="lists.entity.records.links" @clickPage="listEntity"/>
    </div>

    <!-- Modals -->
    <div class="modal fade" :id="forms.entity.createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.createUpdate.extras.modals.default.titles[forms.entity.createUpdate.extras.modals.default.type]"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div v-if="['store'].includes(forms.entity.createUpdate.extras.modals.default.type)" class="d-flex flex-wrap justify-content-center align-items-center mb-3 shadow-sm px-4 py-2 border border-1 border-light rounded">
                        <label class="fw-semibold text-dark d-block mb-2 mb-md-1 text-center">Selecciona el modo de asistencia</label>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <template v-for="record in formModes" :key="record.code">
                                <button :class="['btn btn-sm rounded-pill', [forms.entity.createUpdate.config.currentMode].includes(record?.code) ? 'btn-success fw-bold pe-none' : 'btn-outline-success fw-semibold']" @click="[forms.entity.createUpdate.config.currentMode].includes(record?.code) ? '' : selectModeEntity(record?.code, false)">
                                    <i :class="['fa', record?.icon]"></i>
                                    <span class="ms-1 ms-md-2 d-none d-sm-inline" v-text="record?.label"></span>
                                    <span class="ms-1 ms-md-2 d-inline d-sm-none" v-text="record?.label_sm"></span>
                                </button>
                            </template>
                        </div>
                        <!-- <a class="text-muted" @click="rememberModeEntity('manual')" href="javascript:void(0)">
                            <span>¿Recordar modalidad?</span>
                            <span :class="[forms.entity.createUpdate.config.rememberMode ? 'text-success' : 'text-primary', 'fw-bold ms-1']" v-text="forms.entity.createUpdate.config.rememberMode ? 'Sí' : 'No'"></span>
                        </a> -->
                    </div>
                    <div class="row g-3">
                        <InputSlot
                            hasDiv
                            title="Sucursal"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <span v-if="isDefined({value: forms.entity.createUpdate.data?.id})" v-text="forms.entity.createUpdate.data?.branch?.data?.name" class="fw-semibold"></span>
                                <v-select
                                    v-else
                                    v-model="forms.entity.createUpdate.data.branch"
                                    :options="branches"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    :searchable="false"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            title="Cliente"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <span v-if="isDefined({value: forms.entity.createUpdate.data?.id})" v-text="forms.entity.createUpdate.data?.customer?.label" class="fw-semibold"></span>
                                <v-select
                                    v-else
                                    v-model="forms.entity.createUpdate.data.customer"
                                    :options="customers"
                                    :class="config.forms.classes.select2"
                                    :clearable="true"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="['store', 'finalized', 'canceled'].includes(forms.entity.createUpdate.extras.modals.default.type)"
                            hasDiv
                            :title="['store'].includes(forms.entity.createUpdate.extras.modals.default.type) ? 'Ingreso / salida' : 'Ingreso'"
                            isRequired
                            xl="6"
                            lg="6">
                            <template v-slot:defaultAppend>
                                <small v-if="['store'].includes(forms.entity.createUpdate.extras.modals.default.type)" class="mt-1 text-muted fw-semibold ms-2">Es referencial</small>
                            </template>
                            <template v-slot:input>
                                <AnalogClock
                                    :time="forms.entity.createUpdate.data.start_date"
                                    :isDynamic="['store'].includes(forms.entity.createUpdate.extras.modals.default.type)"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="['finalized', 'canceled'].includes(forms.entity.createUpdate.extras.modals.default.type)"
                            hasDiv
                            :title="['finalized'].includes(forms.entity.createUpdate.extras.modals.default.type) ? 'Salida' : 'Salida'"
                            isRequired
                            xl="6"
                            lg="6">
                            <template v-slot:defaultAppend>
                                <small v-if="['finalized'].includes(forms.entity.createUpdate.extras.modals.default.type)" class="mt-1 text-muted fw-semibold ms-2">Es referencial</small>
                            </template>
                            <template v-slot:input>
                                <AnalogClock
                                    :time="forms.entity.createUpdate.data.end_date"
                                    :isDynamic="['finalized'].includes(forms.entity.createUpdate.extras.modals.default.type)"/>
                            </template>
                        </InputSlot>
                    </div>
                    <!-- <div v-if="['store'].includes(forms.entity.createUpdate.extras.modals.default.type)" class="alert alert-secondary small mt-3 mb-0" role="alert">
                        El sistema detectará automáticamente si estás registrando un ingreso o una salida.
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" :class="['btn waves-effect', forms.entity.createUpdate.extras.modals.default.config.buttons[forms.entity.createUpdate.extras.modals.default.type]]" @click="['canceled'].includes(forms.entity.createUpdate.extras.modals.default.type) ? cancelEntity({}) : createUpdateEntity()">
                        <i :class="forms.entity.createUpdate.extras.modals.default.config.icons[forms.entity.createUpdate.extras.modals.default.type]"></i>
                        <span class="ms-2" v-text="forms.entity.createUpdate.extras.modals.default.config.labels[forms.entity.createUpdate.extras.modals.default.type]"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" :id="forms.entity.qrCamera.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.qrCamera.extras.modals.default.titles[forms.entity.qrCamera.extras.modals.default.type]"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div v-if="['store'].includes(forms.entity.qrCamera.extras.modals.default.type)" class="d-flex flex-wrap justify-content-center align-items-center mb-3 shadow-sm px-4 py-2 border border-1 border-light rounded">
                        <label class="fw-semibold text-dark d-block mb-2 mb-md-1 text-center">Selecciona el modo de asistencia</label>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <template v-for="record in formModes" :key="record.code">
                                <button :class="['btn btn-sm rounded-pill', [forms.entity.createUpdate.config.currentMode].includes(record?.code) ? 'btn-success fw-bold pe-none' : 'btn-outline-success fw-semibold']" @click="[forms.entity.createUpdate.config.currentMode].includes(record?.code) ? '' : selectModeEntity(record?.code, false)">
                                    <i :class="['fa', record?.icon]"></i>
                                    <span class="ms-1 ms-md-2 d-none d-sm-inline" v-text="record?.label"></span>
                                    <span class="ms-1 ms-md-2 d-inline d-sm-none" v-text="record?.label_sm"></span>
                                </button>
                            </template>
                        </div>
                        <!-- <a class="text-muted" @click="rememberModeEntity('qrCamera')" href="javascript:void(0)">
                            <span>¿Recordar modalidad?</span>
                            <span :class="[forms.entity.createUpdate.config.rememberMode ? 'text-success' : 'text-primary', 'fw-bold ms-1']" v-text="forms.entity.createUpdate.config.rememberMode ? 'Sí' : 'No'"></span>
                        </a> -->
                    </div>
                    <div class="row g-3">
                        <InputSlot
                            hasDiv
                            title="Sucursal"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <span v-if="isDefined({value: forms.entity.qrCamera.data?.id})" v-text="forms.entity.qrCamera.data?.branch?.data?.name" class="fw-semibold"></span>
                                <v-select
                                    v-else
                                    v-model="forms.entity.qrCamera.data.branch"
                                    :options="branches"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    :searchable="false"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            title="Cámara interna"
                            :isInputGroup="false"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <template v-if="forms.entity.qrCamera.config.isProcessing">
                                    <div class="d-flex align-items-center justify-content-center p-2 bg-light border rounded">
                                        <div class="spinner-border text-dark me-2"></div>
                                        <span class="fw-semibold">Finalizando escaneo ...</span>
                                    </div>
                                </template>
                                <div class="w-100" v-show="!forms.entity.qrCamera.config.isProcessing">
                                    <CodeScanner
                                        ref="scannerQr"
                                        :showControls="true"
                                        :qrbox="290"
                                        :fps="23"
                                        :limitScan="-1"
                                        :canProcess="forms.entity.qrCamera.config.isCurrentActive && !forms.entity.qrCamera.config.isProcessing"
                                        @result="onResultQrCamera"/>
                                </div>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="(forms.entity.qrCamera.data.customers).length > 1"
                            hasDiv
                            title="Clientes"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <div class="table-responsive w-100">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr class="text-center align-middle">
                                                <th class="bg-secondary text-white fw-semibold" style="width: 10%;">#</th>
                                                <th class="bg-secondary text-white fw-semibold" style="width: 75%;">CLIENTE</th>
                                                <th class="bg-secondary text-white fw-semibold" style="width: 15%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0 bg-white">
                                            <template v-if="(forms.entity.qrCamera.data.customers).length > 0">
                                                <template v-for="(record, keyRecord) in forms.entity.qrCamera.data.customers" :key="record.id">
                                                    <tr class="text-nowrap text-center">
                                                        <td v-text="keyRecord + 1"></td>
                                                        <td v-text="record.label"></td>
                                                        <td>
                                                            <button class="btn btn-danger btn-xs waves-effect" type="button" @click="deleteQrCameraEntity({record, keyRecord})">
                                                                <i class="fa fa-times"></i>
                                                                <span class="ms-1">Eliminar</span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </template>
                                            <template v-else>
                                                <tr>
                                                    <td class="text-center" colspan="99">
                                                        <WithoutData type="text"/>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="['store', 'finalized', 'canceled'].includes(forms.entity.qrCamera.extras.modals.default.type)"
                            hasDiv
                            :title="['store'].includes(forms.entity.qrCamera.extras.modals.default.type) ? 'Ingreso / salida' : 'Ingreso'"
                            isRequired
                            xl="6"
                            lg="6">
                            <template v-slot:defaultAppend>
                                <small v-if="['store'].includes(forms.entity.qrCamera.extras.modals.default.type)" class="mt-1 text-muted fw-semibold ms-2">Es referencial</small>
                            </template>
                            <template v-slot:input>
                                <AnalogClock
                                    :time="forms.entity.qrCamera.data.start_date"
                                    :isDynamic="['store'].includes(forms.entity.qrCamera.extras.modals.default.type)"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="['finalized', 'canceled'].includes(forms.entity.qrCamera.extras.modals.default.type)"
                            hasDiv
                            :title="['finalized'].includes(forms.entity.qrCamera.extras.modals.default.type) ? 'Salida' : 'Salida'"
                            isRequired
                            xl="6"
                            lg="6">
                            <template v-slot:defaultAppend>
                                <small v-if="['finalized'].includes(forms.entity.qrCamera.extras.modals.default.type)" class="mt-1 text-muted fw-semibold ms-2">Es referencial</small>
                            </template>
                            <template v-slot:input>
                                <AnalogClock
                                    :time="forms.entity.qrCamera.data.end_date"
                                    :isDynamic="['finalized'].includes(forms.entity.qrCamera.extras.modals.default.type)"/>
                            </template>
                        </InputSlot>
                    </div>
                    <!-- <div v-if="['store'].includes(forms.entity.qrCamera.extras.modals.default.type)" class="alert alert-secondary small mt-3 mb-0" role="alert">
                        El sistema detectará automáticamente si estás registrando un ingreso o una salida.
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                    <!-- <button type="button" :class="['btn waves-effect', forms.entity.qrCamera.extras.modals.default.config.buttons[forms.entity.qrCamera.extras.modals.default.type]]" @click="['canceled'].includes(forms.entity.qrCamera.extras.modals.default.type) ? cancelEntity({}) : qrCameraEntity()">
                        <i :class="forms.entity.qrCamera.extras.modals.default.config.icons[forms.entity.qrCamera.extras.modals.default.type]"></i>
                        <span class="ms-2" v-text="forms.entity.qrCamera.extras.modals.default.config.labels[forms.entity.qrCamera.extras.modals.default.type]"></span>
                    </button> -->
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" :id="forms.entity.qrScanner.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.qrScanner.extras.modals.default.titles[forms.entity.qrScanner.extras.modals.default.type]"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div v-if="['store'].includes(forms.entity.qrScanner.extras.modals.default.type)" class="d-flex flex-wrap justify-content-center align-items-center mb-3 shadow-sm px-4 py-2 border border-1 border-light rounded">
                        <label class="fw-semibold text-dark d-block mb-2 mb-md-1 text-center">Selecciona el modo de asistencia</label>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <template v-for="record in formModes" :key="record.code">
                                <button :class="['btn btn-sm rounded-pill', [forms.entity.createUpdate.config.currentMode].includes(record?.code) ? 'btn-success fw-bold pe-none' : 'btn-outline-success fw-semibold']" @click="[forms.entity.createUpdate.config.currentMode].includes(record?.code) ? '' : selectModeEntity(record?.code, false)">
                                    <i :class="['fa', record?.icon]"></i>
                                    <span class="ms-1 ms-md-2 d-none d-sm-inline" v-text="record?.label"></span>
                                    <span class="ms-1 ms-md-2 d-inline d-sm-none" v-text="record?.label_sm"></span>
                                </button>
                            </template>
                        </div>
                        <!-- <a class="text-muted" @click="rememberModeEntity('qrScanner')" href="javascript:void(0)">
                            <span>¿Recordar modalidad?</span>
                            <span :class="[forms.entity.createUpdate.config.rememberMode ? 'text-success' : 'text-primary', 'fw-bold ms-1']" v-text="forms.entity.createUpdate.config.rememberMode ? 'Sí' : 'No'"></span>
                        </a> -->
                    </div>
                    <div class="row g-3">
                        <InputSlot
                            hasDiv
                            title="Sucursal"
                            isRequired
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <span v-if="isDefined({value: forms.entity.qrScanner.data?.id})" v-text="forms.entity.qrScanner.data?.branch?.data?.name" class="fw-semibold"></span>
                                <v-select
                                    v-else
                                    v-model="forms.entity.qrScanner.data.branch"
                                    :options="branches"
                                    :class="config.forms.classes.select2"
                                    :clearable="false"
                                    :searchable="false"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            hasDiv
                            title="Selecciona el tipo de documento"
                            :isInputGroup="false"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <template v-if="forms.entity.qrScanner.config.isProcessing">
                                    <div class="d-flex align-items-center justify-content-center p-2 bg-light border rounded">
                                        <div class="spinner-border text-dark me-2"></div>
                                        <span class="fw-semibold">Finalizando escaneo ...</span>
                                    </div>
                                </template>
                                <div v-else class="d-flex flex-wrap justify-content-center align-items-center gap-2 gap-md-3">
                                    <div class="text-center position-relative cursor-pointer mt-1" v-for="record in qrCameraModes" :key="record.code">
                                        <div :class="['card border shadow-sm', 'border-light', [forms.entity.qrScanner.config.currentMode].includes(record?.code) ? 'bg-'+record?.color : '']" @click="setModeQrScanner(record.code)" data-bs-toggle="tooltip" data-bs-placement="top" :title="record?.tooltip">
                                            <div class="card-body p-3">
                                                <i :class="[record?.icon, 'fa-xl', [forms.entity.qrScanner.config.currentMode].includes(record?.code) ? 'text-white' : 'text-'+record?.color]"></i>
                                            </div>
                                        </div>
                                        <small :class="['d-block mt-1 fw-bold', 'text-'+record?.color]" v-text="record?.label"></small>
                                    </div>
                                </div>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="!forms.entity.qrScanner.config.isProcessing"
                            hasDiv
                            :isInputGroup="false"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <input
                                    ref="qrInput"
                                    v-model="forms.entity.qrScanner.data.code"
                                    type="text"
                                    class="form-control text-center fw-bold"
                                    placeholder="Escanea el código QR aquí"
                                    @input="onResultQrScanner"
                                    @change="onResultQrScanner"
                                    @keyup.enter="onResultQrScanner"
                                    autocomplete="off"
                                    :disabled="forms.entity.qrScanner.config.isProcessing"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="['store', 'finalized', 'canceled'].includes(forms.entity.qrScanner.extras.modals.default.type)"
                            hasDiv
                            :title="['store'].includes(forms.entity.qrScanner.extras.modals.default.type) ? 'Ingreso / salida' : 'Ingreso'"
                            isRequired
                            xl="6"
                            lg="6">
                            <template v-slot:defaultAppend>
                                <small v-if="['store'].includes(forms.entity.qrScanner.extras.modals.default.type)" class="mt-1 text-muted fw-semibold ms-2">Es referencial</small>
                            </template>
                            <template v-slot:input>
                                <AnalogClock
                                    :time="forms.entity.qrScanner.data.start_date"
                                    :isDynamic="['store'].includes(forms.entity.qrScanner.extras.modals.default.type)"/>
                            </template>
                        </InputSlot>
                        <InputSlot
                            v-if="['finalized', 'canceled'].includes(forms.entity.qrScanner.extras.modals.default.type)"
                            hasDiv
                            :title="['finalized'].includes(forms.entity.qrScanner.extras.modals.default.type) ? 'Salida' : 'Salida'"
                            isRequired
                            xl="6"
                            lg="6">
                            <template v-slot:defaultAppend>
                                <small v-if="['finalized'].includes(forms.entity.qrScanner.extras.modals.default.type)" class="mt-1 text-muted fw-semibold ms-2">Es referencial</small>
                            </template>
                            <template v-slot:input>
                                <AnalogClock
                                    :time="forms.entity.qrScanner.data.end_date"
                                    :isDynamic="['finalized'].includes(forms.entity.qrScanner.extras.modals.default.type)"/>
                            </template>
                        </InputSlot>
                    </div>
                    <!-- <div v-if="['store'].includes(forms.entity.qrScanner.extras.modals.default.type)" class="alert alert-secondary small mt-3 mb-0" role="alert">
                        El sistema detectará automáticamente si estás registrando un ingreso o una salida.
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                    <!-- <button type="button" :class="['btn waves-effect', forms.entity.qrScanner.extras.modals.default.config.buttons[forms.entity.qrScanner.extras.modals.default.type]]" @click="['canceled'].includes(forms.entity.qrScanner.extras.modals.default.type) ? cancelEntity({}) : qrScannerEntity()">
                        <i :class="forms.entity.qrScanner.extras.modals.default.config.icons[forms.entity.qrScanner.extras.modals.default.type]"></i>
                        <span class="ms-2" v-text="forms.entity.qrScanner.extras.modals.default.config.labels[forms.entity.qrScanner.extras.modals.default.type]"></span>
                    </button> -->
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" :id="forms.entity.automatic.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.automatic.extras.modals.default.titles[forms.entity.automatic.extras.modals.default.type]"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr class="text-center align-middle">
                                        <th class="bg-secondary text-white fw-semibold" style="width: 10%;">#</th>
                                        <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 20%;">SUCURSAL</th>
                                        <th class="bg-secondary text-white fw-semibold min-w-150pxs" style="width: 30%;">DIRECCIÓN</th>
                                        <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 40%;">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0 bg-white">
                                    <template v-if="(forms.entity.automatic.extras.modals.default.config.branches).length > 0">
                                        <tr v-for="(record, indexRecord) in forms.entity.automatic.extras.modals.default.config.branches" :key="record.code" class="text-center">
                                            <td v-text="indexRecord + 1"></td>
                                            <td class="text-start">
                                                <span v-text="record.label" class="fw-bold d-block"></span>
                                            </td>
                                            <td v-text="isDefined({value: record.data?.address}) ? record.data?.address : 'N/A'" class="text-start"></td>
                                            <td>
                                                <InputSlot
                                                    hasDiv
                                                    :isInputGroup="false"
                                                    :divInputClass="['d-flex flex-wrap justify-content-center gap-2 gap-md-1']"
                                                    xl="12"
                                                    lg="12">
                                                    <template v-slot:input>
                                                        <button type="button" class="btn btn-sm btn-success waves-effect" @click="openAutomaticEntity({record})">
                                                            <i class="fa fa-camera"></i>
                                                            <span class="ms-2 text-nowrap">Iniciar escaneo</span>
                                                        </button>
                                                    </template>
                                            </InputSlot>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td class="text-center" colspan="99">
                                                <WithoutData type="image"/>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";

let defaultChartInstance = null;
let doughnutChartInstance = null;

export default {
    components: {
        //
    },
    mounted: async function() {

        let el = this;

        Utils.navbarItem("menu-parent-customers", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        let initParams = await this.initParams({}),
            initOthers = await this.initOthers({});

        if(initParams && initOthers) {

            Alerts.swals({show: false});
            // this.listEntity({});

            $(`#${this.forms.entity.qrCamera.extras.modals.default.id}`)
            .on("shown.bs.modal", () => {

                el.forms.entity.qrCamera.config.isCurrentActive = true;

            })
            .on("hidden.bs.modal", () => {

                el.forms.entity.qrCamera.config.isCurrentActive = false;

            });

        }

    },
    data() {
        return {
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        route: Requests.config({entity: "tracking_attendances", type: "list"})
                    },
                    filters: {
                        branch: null,
                        customer: null,
                        start_date: "",
                        status: ""
                    },
                    records: {
                        total: 0
                    }
                }
            },
            forms: {
                entity: {
                    createUpdate: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        store: "Registrar",
                                        update: "Editar",
                                        finalized: "Concluir",
                                        canceled: "Anular"
                                    },
                                    config: {
                                        buttons: {
                                            store: "btn-primary",
                                            update: "btn-warning",
                                            finalized: "btn-warning",
                                            canceled: "btn-danger"
                                        },
                                        icons: {
                                            store: "fa fa-check",
                                            update: "fa fa-save",
                                            finalized: "fa-solid fa-person-walking-arrow-right",
                                            canceled: "fa fa-times"
                                        },
                                        labels: {
                                            store: "Registrar asistencia",
                                            update: "Editar asistencia",
                                            finalized: "Concluir asistencia",
                                            canceled: "Anular asistencia"
                                        }
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            branch: null,
                            customer: null,
                            start_date: "",
                            end_date: "",
                            status: null
                        },
                        config: {
                            viewFilters: false,
                            rememberMode: true,
                            currentMode: "",
                            historyMode: []
                        },
                        errors: {}
                    },
                    qrCamera: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        store: "Registrar",
                                        update: "Editar",
                                        finalized: "Concluir",
                                        canceled: "Anular"
                                    },
                                    config: {
                                        buttons: {
                                            store: "btn-primary",
                                            update: "btn-warning",
                                            finalized: "btn-warning",
                                            canceled: "btn-danger"
                                        },
                                        icons: {
                                            store: "fa fa-check",
                                            update: "fa fa-save",
                                            finalized: "fa-solid fa-person-walking-arrow-right",
                                            canceled: "fa fa-times"
                                        },
                                        labels: {
                                            store: "Registrar asistencia",
                                            update: "Editar asistencia",
                                            finalized: "Concluir asistencia",
                                            canceled: "Anular asistencia"
                                        }
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            branch: null,
                            customers: [],
                            start_date: "",
                            end_date: "",
                            status: null
                        },
                        config: {
                            firstInit: false,
                            isCurrentActive: false,
                            isProcessing: true
                        },
                        errors: {}
                    },
                    qrScanner: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        store: "Registrar",
                                        update: "Editar",
                                        finalized: "Concluir",
                                        canceled: "Anular"
                                    },
                                    config: {
                                        buttons: {
                                            store: "btn-primary",
                                            update: "btn-warning",
                                            finalized: "btn-warning",
                                            canceled: "btn-danger"
                                        },
                                        icons: {
                                            store: "fa fa-check",
                                            update: "fa fa-save",
                                            finalized: "fa-solid fa-person-walking-arrow-right",
                                            canceled: "fa fa-times"
                                        },
                                        labels: {
                                            store: "Registrar asistencia",
                                            update: "Editar asistencia",
                                            finalized: "Concluir asistencia",
                                            canceled: "Anular asistencia"
                                        }
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            code: "",
                            branch: null,
                            customers: [],
                            start_date: "",
                            end_date: "",
                            status: null
                        },
                        config: {
                            currentMode: "",
                            isProcessing: true
                        },
                        errors: {}
                    },
                    automatic: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        generate: "Sucursales"
                                    },
                                    config: {
                                        branches: []
                                    }
                                }
                            }
                        },
                        data: {},
                        errors: {}
                    }
                }
            },
            options: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "tracking_attendances"}),
                    page: {
                        title: "Asistencia",
                        active: true,
                        menu: {
                            id: "menu-customers-attendances"
                        }
                    }
                }
            }
        };
    },
    methods: {
        // ============================================
        // Initialization Methods
        // ============================================
        async initParams({}) {

            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(initParams?.data?.config) {

                this.options.branches  = initParams.data.config.branches;
                this.options.customers = initParams.data.config.customers;

            }

            return Requests.valid({result: initParams});

        },
        async initOthers({}) {

            return new Promise(resolve => {

                this.lists.entity.filters.branch     = this.branches[0];
                this.lists.entity.filters.start_date = Utils.getCurrentDate("date");

                this.adjustChartHeights();

                resolve(true);

            });

        },
        adjustChartHeights() {

            const chartList = document.querySelectorAll(".chartjs");

            chartList.forEach(function(chartListItem) {

                chartListItem.height = chartListItem.dataset.height;

            });

        },
        initChart(type = "default") {

            if(type === "default") {

                // Utils
                const roundUpToNearest = (value) => {
                    if (value <= 20) return 20;
                    if (value <= 50) return 50;
                    if (value <= 100) return 100;
                    return Math.ceil(value / 500) * 500;
                };

                // Config
                const intervals = [
                    { label: "12:00 AM", start: 0, end: 3 },
                    { label: "03:00 AM", start: 3, end: 6 },
                    { label: "06:00 AM", start: 6, end: 9 },
                    { label: "09:00 AM", start: 9, end: 12 },
                    { label: "12:00 PM", start: 12, end: 15 },
                    { label: "03:00 PM", start: 15, end: 18 },
                    { label: "06:00 PM", start: 18, end: 21 },
                    { label: "09:00 PM", start: 21, end: 24 }
                ];

                const totalsByIntervalStart = intervals.map(e => ({ label: e.label, total: 0 }));
                const totalsByIntervalEnd   = intervals.map(e => ({ label: e.label, total: 0 }));

                // Process data
                const records = this.lists.entity.records.data;

                records.forEach(record => {

                    const startHour = new Date(record.start_date).getHours();
                    const startInterval = intervals.find(i => startHour >= i.start && startHour < i.end);

                    if(this.isDefined({value: startInterval})) {

                        const index = intervals.indexOf(startInterval);
                        totalsByIntervalStart[index].total += 1;

                    }

                    if(this.isDefined({value: record.end_date})) {

                        const endHour = new Date(record.end_date).getHours();
                        const endInterval = intervals.find(i => endHour >= i.start && endHour < i.end);

                        if(endInterval) {

                            const index = intervals.indexOf(endInterval);
                            totalsByIntervalEnd[index].total += 1;

                        }

                    }

                });

                // Get information
                let dataStart = totalsByIntervalStart.map(i => i.total);
                let dataEnd = totalsByIntervalEnd.map(i => i.total)

                const defaultChart = document.getElementById("defaultChartId");
                const labels = totalsByIntervalStart.map(i => i.label);
                const yMax   = roundUpToNearest(Math.max(...dataStart));

                if(defaultChart) {

                    if(defaultChartInstance) {

                        defaultChartInstance.destroy();

                    }

                    defaultChartInstance = new Chart(defaultChart, {
                        type: "line",
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: "Entradas",
                                    data: dataStart,
                                    fill: false,
                                    borderColor: this.config.colors.charts.default.defaultColor,
                                    pointBackgroundColor: "#fff",
                                    pointBorderColor: this.config.colors.charts.default.defaultColor,
                                    tension: 0.2
                                },
                                {
                                    label: "Salidas",
                                    data: dataEnd,
                                    fill: false,
                                    borderColor: this.config.colors.charts.default.dangerColor,
                                    pointBackgroundColor: "#fff",
                                    pointBorderColor: this.config.colors.charts.default.dangerColor,
                                    tension: 0.2
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 500
                            },
                            plugins: {
                                tooltip: {
                                    backgroundColor: this.config.colors.charts.default.backgroundColor,
                                    bodyColor: this.config.colors.charts.default.bodyColor,
                                    borderColor: this.config.colors.charts.default.borderColor,
                                    borderWidth: 1,
                                    rtl: false,
                                    titleColor: this.config.colors.charts.default.titleColor
                                },
                                legend: {
                                    display: true,
                                    labels: {
                                        color: this.config.colors.charts.default.labelColor
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        color: this.config.colors.charts.default.borderColor,
                                        drawBorder: false,
                                        borderColor: this.config.colors.charts.default.borderColor
                                    },
                                    ticks: {
                                        color: this.config.colors.charts.default.labelColor
                                    }
                                },
                                y: {
                                    min: 0,
                                    max: yMax,
                                    grid: {
                                        color: this.config.colors.charts.default.borderColor,
                                        drawBorder: false,
                                        borderColor: this.config.colors.charts.default.borderColor
                                    },
                                    ticks: {
                                        stepSize: yMax / 4,
                                        color: this.config.colors.charts.default.labelColor
                                    }
                                }
                            }
                        }
                    });

                };

            }else if(type === "doughnut") {

                // Config
                const keyDoughnut = {
                    active: 0,
                    finalized: 0,
                    canceled: 0
                };

                // Process data
                const records = this.lists.entity.records.data;

                records.forEach(record => {
                    if (record.status === "active") keyDoughnut.active++;
                    if (record.status === "finalized") keyDoughnut.finalized++;
                    if (record.status === "canceled") keyDoughnut.canceled++;
                });

                const doughnutChart = document.getElementById("doughnutChartId");

                if(doughnutChart) {

                    if(doughnutChartInstance) {

                        doughnutChartInstance.destroy();

                    }

                    doughnutChartInstance = new Chart(doughnutChart, {
                        type: "doughnut",
                        data: {
                            labels: [`En curso (${keyDoughnut.active})`, `Concluida (${keyDoughnut.finalized})`, `Anulada (${keyDoughnut.canceled})`],
                            datasets: [{
                                data: [
                                    keyDoughnut.active,
                                    keyDoughnut.finalized,
                                    keyDoughnut.canceled
                                ],
                                backgroundColor: [
                                    this.config.colors.charts.default.successColor,
                                    this.config.colors.charts.default.primaryColor,
                                    this.config.colors.charts.default.dangerColor
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: "bottom",
                                    labels: {
                                        color: this.config.colors.charts.default.labelColor
                                    }
                                },
                                tooltip: {
                                    backgroundColor: this.config.colors.charts.default.backgroundColor,
                                    bodyColor: this.config.colors.charts.default.bodyColor,
                                    borderColor: this.config.colors.charts.default.borderColor,
                                    borderWidth: 1
                                }
                            }
                        }
                    });
                }

            }

        },
        // ============================================
        // Entity List Methods
        // ============================================
        async listEntity({url = null}) {

            const filters = Utils.cloneJson(this.lists.entity.filters);
            const filterJson = {
                branch_id:   filters?.branch?.code,
                customer_id: filters?.customer?.code,
                start_date:  filters?.start_date,
                status:      filters?.status
            };

            this.lists.entity.extras.loading = true;

            try {

                const response = await Requests.get({
                    route: url || this.lists.entity.extras.route,
                    data: filterJson
                });

                this.lists.entity.records = response?.data;

            }finally {

                this.lists.entity.extras.loading = false;

            }

            this.initChart("default");
            this.initChart("doughnut");

        },
        async exportEntity() {

            const filters = Utils.cloneJson(this.lists.entity.filters);
            const params = {};

            if(this.isDefined({value: filters?.branch?.code})) params.branch_id = filters.branch.code;
            if(this.isDefined({value: filters?.customer?.code})) params.customer_id = filters.customer.code;
            if(this.isDefined({value: filters?.start_date})) params.start_date = filters.start_date;
            if(this.isDefined({value: filters?.status})) params.status = filters.status;

            Alerts.swals({type: "loading", message: "Preparando exportación"});

            try {

                await Requests.download({
                    route: this.config.entity.routes.export,
                    data: params,
                    fileName: "asistencias-clientes.xlsx",
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});

            }

        },
        async requestCorrectionEntity(record) {

            const self = this;

            const result = await Swal.fire({
                title: "Solicitar corrección",
                html: `<div class="text-start">
                           <p class="small text-muted mb-3">Indica las fechas correctas solo si deben cambiar. El motivo es obligatorio para auditoría.</p>
                           <div class="row g-2">
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Ingreso corregido</label>
                                   <input type="datetime-local" class="form-control" id="attendanceCorrectionStart">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label colon-at-end">Salida corregida</label>
                                   <input type="datetime-local" class="form-control" id="attendanceCorrectionEnd">
                               </div>
                               <div class="col-12">
                                   <label class="form-label colon-at-end">Motivo</label>
                                   <textarea class="form-control no-resize" maxlength="500" rows="3" id="attendanceCorrectionReason"></textarea>
                               </div>
                           </div>
                       </div>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: "Registrar corrección",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: "btn btn-primary waves-effect",
                    cancelButton: "btn btn-secondary waves-effect ms-3"
                },
                preConfirm() {

                    const startDate = document.getElementById("attendanceCorrectionStart")?.value;
                    const endDate = document.getElementById("attendanceCorrectionEnd")?.value;
                    const reason = document.getElementById("attendanceCorrectionReason")?.value?.trim();

                    if(!reason) {

                        Swal.showValidationMessage("Ingresa el motivo de la corrección.");
                        return false;

                    }

                    if(startDate && endDate && startDate >= endDate) {

                        Swal.showValidationMessage("La salida corregida debe ser mayor al ingreso corregido.");
                        return false;

                    }

                    return {
                        start_date: startDate ? startDate.replace("T", " ") : null,
                        end_date: endDate ? endDate.replace("T", " ") : null,
                        reason
                    };

                }
            });

            if(!result.isConfirmed) return;

            Alerts.swals({type: "loading", message: "Registrando corrección"});

            try {

                const route = `${this.config.entity.routes.consult}/${record.id}/corrections`;
                const response = await Requests.post({route, data: result.value});

                if(Requests.valid({result: response})) {

                    Alerts.swals({show: false});
                    Alerts.generateAlert({type: "success", msgContent: response?.data?.msg || "Corrección registrada correctamente."});
                    await self.listEntity({});

                }else {

                    Alerts.swals({show: false});
                    Alerts.generateAlert({type: "warning", msgContent: response?.data?.msg || "No fue posible registrar la corrección."});

                }

            }finally {

                Alerts.swals({show: false});

            }

        },

        // ============================================
        // Mode Management Methods
        // ============================================
        rememberModeEntity(mode = "manual") {

            this.forms.entity.createUpdate.config.rememberMode = !this.forms.entity.createUpdate.config.rememberMode;

            if(this.forms.entity.createUpdate.config.rememberMode) {

                this.addModeEntity(mode);

            }

        },
        addModeEntity(mode = "manual") {

            this.forms.entity.createUpdate.config.historyMode.push(mode);

        },
        selectModeEntity(mode = "manual", validateRemember = true) {

            let el = this;

            let configMode = "";

            if(validateRemember && this.forms.entity.createUpdate.config.rememberMode) {

                configMode = this.forms.entity.createUpdate.config.historyMode[this.forms.entity.createUpdate.config.historyMode.length - 1] ?? "manual";

                this.addModeEntity(configMode);
                this.forms.entity.createUpdate.config.currentMode = configMode;

            }else {

                configMode = mode;

                this.addModeEntity(configMode);
                this.forms.entity.createUpdate.config.currentMode = configMode;

            }

            this.toogleProcessingEntity({type: "qrCamera", isProcessing: true});

            Alerts.modals({type: "hide", id: this.forms.entity.createUpdate.extras.modals.default.id});
            Alerts.modals({type: "hide", id: this.forms.entity.qrCamera.extras.modals.default.id});
            Alerts.modals({type: "hide", id: this.forms.entity.qrScanner.extras.modals.default.id});

            if(configMode == "manual") {

                this.modalCreateUpdateEntity({type: "store"});

            }else if(configMode == "qrCamera") {

                if(!this.forms.entity.qrCamera.config.firstInit) {

                    this.$refs.scannerQr.startScanner();

                    this.forms.entity.qrCamera.config.firstInit = true;

                }

                this.modalQrCameraEntity({type: "store"});

            }else if(configMode == "qrScanner") {

                if(!this.isDefined({value: this.forms.entity.qrScanner.config.currentMode})) {

                    this.setModeQrScanner(this.qrCameraModes[0]?.code);

                }

                this.modalQrScannerEntity({type: "store"});

            }

        },
        toogleProcessingEntity({type, isProcessing, time = 0}) {

            let el = this;

            if(time > 0) {

                setTimeout(() => el.forms.entity[type].config.isProcessing = isProcessing, time);

            }else {

                this.forms.entity[type].config.isProcessing = isProcessing;

            };

        },
        modalCreateUpdateEntity({record = null, type = "store"}) {

            const functionName = "modalCreateUpdateEntity";

            this.forms.entity.createUpdate.extras.modals.default.type = type;

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                const branch   = this.branches.filter(e => e.code === record?.branch?.id)[0],
                      customer = this.customers.filter(e => e.code === record?.customer?.id)[0];

                this.forms.entity.createUpdate.data.id         = record?.id;
                this.forms.entity.createUpdate.data.branch     = branch;
                this.forms.entity.createUpdate.data.customer   = customer;
                this.forms.entity.createUpdate.data.start_date = record.start_date;
                this.forms.entity.createUpdate.data.end_date   = this.isDefined({value: record.end_date}) ? record.end_date : Utils.getCurrentDate("datetime");

            }else {

                this.forms.entity.createUpdate.data.branch     = this.lists.entity.filters.branch;
                this.forms.entity.createUpdate.data.start_date = Utils.getCurrentDate("datetime");

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.createUpdate.extras.modals.default.id});

        },
        async createUpdateEntity() {

            const functionName = "createUpdateEntity";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.createUpdate.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive", modal: this.forms.entity.createUpdate.extras.modals.default}});

            if(validateForm?.bool) {

                form.branch_id   = form?.branch?.code;
                form.customer_id = form?.customer?.code;

                delete form.branch;
                delete form.customer;

                const createUpdate = await (this.isDefined({value: form.id}) ? Requests.patch({route: this.config.entity.routes.update, data: form, id: form.id}) :
                                                                               Requests.post({route: this.config.entity.routes.store, data: form}));

                const isValid = Requests.valid({result: createUpdate});

                if(isValid) {

                    if(["finalized"].includes(this.forms.entity.createUpdate.extras.modals.default.type)) {

                        Alerts.modals({type: "hide", id: this.forms.entity.createUpdate.extras.modals.default.id});

                    }

                    // Alerts.toastrs({type: "success", subtitle: createUpdate?.data?.msg});
                    // Alerts.swals({show: false});

                    this.clearForm({functionName});
                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: createUpdate?.errors ?? []});
                    // Alerts.toastrs({type: "error", subtitle: createUpdate?.data?.msg});
                    // Alerts.swals({show: false});

                }

                // Show response
                if((createUpdate?.data?.attendances ?? []).length === 0) {

                    Alerts.generateAlert({type: isValid ? "success" : "error", msgContent: createUpdate?.data?.msg});

                }else if((createUpdate?.data?.attendances ?? []).length === 1) {

                    Alerts.generateAlert({type: createUpdate?.data?.attendances[0]?.bool ? "success" : "error", msgContent: createUpdate?.data?.attendances[0]?.msg});

                }else {

                    Alerts.generateAlert({type: isValid ? "success" : "error", messages: createUpdate?.data?.attendances.map(e => `${e.bool ? "<i class='fa fa-check-circle text-success'></i>" : "<i class='fa fa-times-circle text-danger'></i>"} <span class='ms-1'>${e.msg}</span>`), msgContent: `<div class="fw-semibold mb-2">${createUpdate?.data?.msg}</div>`});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

        },
        // QrCamera
        modalQrCameraEntity({record = null, type = "store"}) {

            const functionName = "modalQrCameraEntity";

            this.forms.entity.qrCamera.extras.modals.default.type = type;
            this.toogleProcessingEntity({type: "qrCamera", isProcessing: false});

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                /* const branch   = this.branches.filter(e => e.code === record?.branch?.id)[0],
                      customer = this.customers.filter(e => e.code === record?.customer?.id)[0];

                this.forms.entity.qrCamera.data.id         = record?.id;
                this.forms.entity.qrCamera.data.branch     = branch;
                this.forms.entity.qrCamera.data.customer   = customer;
                this.forms.entity.qrCamera.data.start_date = record.start_date;
                this.forms.entity.qrCamera.data.end_date   = this.isDefined({value: record.end_date}) ? record.end_date : Utils.getCurrentDate("datetime"); */

            }else {

                this.forms.entity.qrCamera.data.branch     = this.lists.entity.filters.branch;
                this.forms.entity.qrCamera.data.start_date = Utils.getCurrentDate("datetime");

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.qrCamera.extras.modals.default.id});

        },
        onResultQrCamera(decodedText, decodedResult) {

            const functionName = "onResultQrCamera";

            if(!this.forms.entity.qrCamera.config.isCurrentActive) return;
            if(this.forms.entity.qrCamera.config.isProcessing) return;

            try {

                this.clearForm({functionName});

                console.log(decodedText, decodedResult);
                let dataScan = JSON.parse(decodedResult?.result?.text);

                const dataScanBp = Utils.decodeBase64UTF8(dataScan?.bp);
                const bp = JSON.parse(dataScanBp);

                if(!this.isDefined({value: dataScanBp}) || !this.isDefined({value: bp})) {

                    Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el QR. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});
                    this.$refs.scannerQr.decrementScanCounter();

                }else {

                    const id = parseInt(bp?.id);

                    if(id > 0) {

                        this.toogleProcessingEntity({type: "qrCamera", isProcessing: true});

                        if(this.forms.entity.qrCamera.data.customers.some(e => e.code == id)) {

                            Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block">Cliente escaneado: ${dataScan.name}.</span><span class="d-block fw-semibold mt-1">Ya se encuentra en los clientes escaneados.</span>`});
                            this.$refs.scannerQr.decrementScanCounter();
                            this.toogleProcessingEntity({type: "qrCamera", isProcessing: false, time: 4000});

                        }else {

                            this.forms.entity.qrCamera.data.customers.push({code: id, label: "", data: {id}});
                            this.qrCameraEntity();

                        }

                    }else {

                        Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});
                        this.$refs.scannerQr.decrementScanCounter();

                    }

                }

            }catch(e) {

                console.log(e);
                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el QR. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});
                this.$refs.scannerQr.decrementScanCounter();

            }

        },
        async qrCameraEntity() {

            const functionName = "qrCameraEntity";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.qrCamera.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive", modal: this.forms.entity.qrCamera.extras.modals.default}});

            if(validateForm?.bool) {

                form.branch_id = form?.branch?.code;

                delete form.branch;

                form.customers.forEach(customer => {

                    customer.customer_id = customer.code;

                    delete customer.code;
                    delete customer.label;
                    delete customer.data;

                });

                const qrCamera = await Requests.post({route: this.config.entity.routes.qrCamera, data: form});

                const isValid = Requests.valid({result: qrCamera});

                if(isValid) {

                    if(["finalized"].includes(this.forms.entity.qrCamera.extras.modals.default.type)) {

                        Alerts.modals({type: "hide", id: this.forms.entity.qrCamera.extras.modals.default.id});

                    }

                    // Alerts.toastrs({type: "success", subtitle: qrCamera?.data?.msg});
                    // Alerts.swals({show: false});

                    // this.clearForm({functionName});
                    this.forms.entity.qrCamera.data.customers = [];

                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: qrCamera?.errors ?? []});
                    // Alerts.toastrs({type: "error", subtitle: qrCamera?.data?.msg});
                    // Alerts.swals({show: false});

                }

                this.clearForm({functionName});

                // Show response
                if((qrCamera?.data?.attendances ?? []).length === 0) {

                    Alerts.generateAlert({type: isValid ? "success" : "error", msgContent: qrCamera?.data?.msg});

                }else if((qrCamera?.data?.attendances ?? []).length === 1) {

                    Alerts.generateAlert({type: qrCamera?.data?.attendances[0]?.bool ? "success" : "error", msgContent: qrCamera?.data?.attendances[0]?.msg});

                }else {

                    Alerts.generateAlert({type: isValid ? "success" : "error", messages: qrCamera?.data?.attendances.map(e => `${e.bool ? "<i class='fa fa-check-circle text-success'></i>" : "<i class='fa fa-times-circle text-danger'></i>"} <span class='ms-1'>${e.msg}</span>`), msgContent: `<div class="fw-semibold mb-2">${qrCamera?.data?.msg}</div>`});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            this.toogleProcessingEntity({type: "qrCamera", isProcessing: false, time: 4000});

        },
        deleteQrCameraEntity({record, keyRecord}) {

            const functionName = "deleteQrCameraEntity";

            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(record);

            const validateForm = this.validateForm({functionName, form});

            if(validateForm?.bool) {

                let el = this;

                Swal.fire({
                    html: `<span>¿Desea eliminar a <b>${form?.label}</b> de los clientes escaneados?</span>`,
                    icon: "warning",
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                    customClass: {
                        confirmButton: "btn btn-danger waves-effect",
                        cancelButton: "btn btn-secondary waves-effect ms-3"
                    }
                })
                .then(function(result) {

                    if(result.isConfirmed) {

                        (el.forms.entity.qrCamera.data.customers).splice(keyRecord, 1);

                        Alerts.toastrs({type: "success", subtitle: `<b>${form?.label}</b> ha sido eliminado de los clientes escaneados.`});

                    }else if(result.isDismissed) {

                        //

                    }

                });

            }

        },
        // QrScanner
        setModeQrScanner(mode) {

            if(this.isDefined({value: mode})) {

                this.forms.entity.qrScanner.config.currentMode = mode;

                this.$refs.qrInput?.focus();

            }

        },
        modalQrScannerEntity({record = null, type = "store"}) {

            const functionName = "modalQrScannerEntity";

            this.forms.entity.qrScanner.extras.modals.default.type = type;
            this.toogleProcessingEntity({type: "qrScanner", isProcessing: false});

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                /* const branch   = this.branches.filter(e => e.code === record?.branch?.id)[0],
                      customer = this.customers.filter(e => e.code === record?.customer?.id)[0];

                this.forms.entity.qrScanner.data.id         = record?.id;
                this.forms.entity.qrScanner.data.branch     = branch;
                this.forms.entity.qrScanner.data.customer   = customer;
                this.forms.entity.qrScanner.data.start_date = record.start_date;
                this.forms.entity.qrScanner.data.end_date   = this.isDefined({value: record.end_date}) ? record.end_date : Utils.getCurrentDate("datetime"); */

            }else {

                this.forms.entity.qrScanner.data.branch     = this.lists.entity.filters.branch;
                this.forms.entity.qrScanner.data.start_date = Utils.getCurrentDate("datetime");

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.qrScanner.extras.modals.default.id});

            let el = this;

            if(this.isDefined({value: this.forms.entity.qrScanner.config.currentMode})) {

                setTimeout(() => el.$refs.qrInput?.focus(), 600);

            }

        },
        onResultQrScanner() {

            const functionName = "onResultQrScanner";

            if(this.forms.entity.qrScanner.config.isProcessing) return;

            try {

                const decodedResult = this.forms.entity.qrScanner.data.code.trim();
                const currentMode = this.forms.entity.qrScanner.config.currentMode;

                if(!this.isDefined({value: decodedResult})) return;

                this.clearForm({functionName});

                console.log(currentMode, decodedResult);

                if(["carnet"].includes(currentMode)) {

                    let dataScan = JSON.parse(decodedResult);

                    const dataScanBp = Utils.decodeBase64UTF8(dataScan?.bp);
                    const bp = JSON.parse(dataScanBp);

                    if(!this.isDefined({value: dataScanBp}) || !this.isDefined({value: bp})) {

                        Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el QR. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                    }else {

                        const id = parseInt(bp?.id);

                        if(id > 0) {

                            this.toogleProcessingEntity({type: "qrScanner", isProcessing: true});

                            if(this.forms.entity.qrScanner.data.customers.some(e => e.code == id)) {

                                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block">Cliente escaneado: ${dataScan.name}.</span><span class="d-block fw-semibold mt-1">Ya se encuentra en los clientes escaneados.</span>`});
                                this.toogleProcessingEntity({type: "qrScanner", isProcessing: false, time: 4000});

                            }else {

                                this.forms.entity.qrScanner.data.customers.push({code: id, label: "", customer_attendance_type: currentMode, data: {id}});
                                this.qrScannerEntity();

                            }

                        }else {

                            Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                        }

                    }

                }else if(["document_number"].includes(currentMode)) {

                    let dataScan = decodedResult;

                    const dataScanBp = dataScan;
                    const code = dataScanBp;

                    if(!this.isDefined({value: code})) {

                        Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el documento. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                    }else {

                        const id = parseInt(code);

                        if(id > 0) {

                            this.toogleProcessingEntity({type: "qrScanner", isProcessing: true});

                            if(this.forms.entity.qrScanner.data.customers.some(e => e.code == id)) {

                                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block">Cliente escaneado: ${dataScan.name}.</span><span class="d-block fw-semibold mt-1">Ya se encuentra en los clientes escaneados.</span>`});
                                this.toogleProcessingEntity({type: "qrScanner", isProcessing: false, time: 4000});

                            }else {

                                this.forms.entity.qrScanner.data.customers.push({code: id, label: "", customer_attendance_type: currentMode, data: {id}});
                                this.qrScannerEntity();

                            }

                        }else {

                            Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                        }

                    }

                }

            }catch(e) {

                console.log(e);
                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar lo escaneado. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

            }

        },
        async qrScannerEntity() {

            const functionName = "qrScannerEntity";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.qrScanner.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive", modal: this.forms.entity.qrScanner.extras.modals.default}});

            if(validateForm?.bool) {

                form.branch_id = form?.branch?.code;

                delete form.code;
                delete form.branch;

                form.customers.forEach(customer => {

                    if(["carnet"].includes(customer?.customer_attendance_type)) {

                        customer.customer_id = customer.code;

                    }else if(["document_number", "dni", "dnie"].includes(customer?.customer_attendance_type)) {

                        customer.customer_document_number = customer.code;

                    }

                    delete customer.code;
                    delete customer.label;
                    delete customer.data;

                });

                const qrScanner = await Requests.post({route: this.config.entity.routes.qrScanner, data: form});

                const isValid = Requests.valid({result: qrScanner});

                if(isValid) {

                    if(["finalized"].includes(this.forms.entity.qrScanner.extras.modals.default.type)) {

                        Alerts.modals({type: "hide", id: this.forms.entity.qrScanner.extras.modals.default.id});

                    }

                    // Alerts.toastrs({type: "success", subtitle: qrScanner?.data?.msg});
                    // Alerts.swals({show: false});

                    // this.clearForm({functionName});
                    this.forms.entity.qrScanner.data.customers = [];

                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: qrScanner?.errors ?? []});
                    // Alerts.toastrs({type: "error", subtitle: qrScanner?.data?.msg});
                    // Alerts.swals({show: false});

                }

                this.clearForm({functionName});

                // Show response
                if((qrScanner?.data?.attendances ?? []).length === 0) {

                    Alerts.generateAlert({type: isValid ? "success" : "error", msgContent: qrScanner?.data?.msg});

                }else if((qrScanner?.data?.attendances ?? []).length === 1) {

                    Alerts.generateAlert({type: qrScanner?.data?.attendances[0]?.bool ? "success" : "error", msgContent: qrScanner?.data?.attendances[0]?.msg});

                }else {

                    Alerts.generateAlert({type: isValid ? "success" : "error", messages: qrScanner?.data?.attendances.map(e => `${e.bool ? "<i class='fa fa-check-circle text-success'></i>" : "<i class='fa fa-times-circle text-danger'></i>"} <span class='ms-1'>${e.msg}</span>`), msgContent: `<div class="fw-semibold mb-2">${qrScanner?.data?.msg}</div>`});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            this.toogleProcessingEntity({type: "qrScanner", isProcessing: false, time: 4000});

        },
        cancelEntity({}) {

            const functionName = "cancelEntity";

            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.createUpdate.data);

            const validateForm = this.validateForm({functionName, form});

            Alerts.modals({type: "hide", id: this.forms.entity.createUpdate.extras.modals.default.id});

            if(validateForm?.bool) {

                let el = this;

                Swal.fire({
                    html: `<span class="d-block my-1">¿Desea anular la asistencia de <b>${form?.customer?.label}</b>?</span>
                           <div class="form-group text-start mt-2">
                                <label class="form-label colon-at-end">Motivo</label>
                                <div class="input-group">
                                    <textarea type="text" class="form-control no-resize" maxlength="999" id="motiveId"></textarea>
                                </div>
                           </div>`,
                    icon: "warning",
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: "Sí, anular",
                    cancelButtonText: "Cancelar",
                    customClass: {
                        confirmButton: "btn btn-danger waves-effect",
                        cancelButton: "btn btn-secondary waves-effect ms-3"
                    }
                }).then(async function(result) {

                    if(result.isConfirmed) {

                        const motive = Swal.getHtmlContainer().querySelector("#motiveId").value;

                        Alerts.swals({});

                        let cancel = await Requests.patch({route: el.config.entity.routes.cancel, data: {motive}, id: form.id});

                        if(Requests.valid({result: cancel})) {

                            Alerts.toastrs({type: "success", subtitle: cancel?.data?.msg});
                            Alerts.swals({show: false});

                            el.listEntity({})

                        }else {

                            Alerts.toastrs({type: "error", subtitle: cancel?.data?.msg});
                            Alerts.swals({show: false});

                        }

                    }else if(result.isDismissed) {

                        //

                    }

                })

            }else {

                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            Alerts.tooltips({show: false});

        },
        // Automatic: URL by Branch
        modalAutomaticEntity({type = "store"}) {

            const functionName = "modalAutomaticEntity";

            this.forms.entity.automatic.extras.modals.default.type = type;

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            this.forms.entity.automatic.extras.modals.default.config.branches = this.branches;

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.automatic.extras.modals.default.id});

        },
        openAutomaticEntity({record = null}) {

            if(this.isDefined({value: record})) {

                const url = generateMyUrl(window.company, false, "my_web", {section: "tracking_attendances", branch: {id: record?.code}});

                window.open(url, "_blank");

            }

        },
        // Forms utils
        clearForm({functionName}) {

            switch(functionName) {
                case "modalCreateUpdateEntity":
                case "createUpdateEntity":
                    this.forms.entity.createUpdate.data.id       = null;
                    this.forms.entity.createUpdate.data.customer = null;
                    this.forms.entity.createUpdate.data.end_date = "";
                    this.forms.entity.createUpdate.data.status   = null;

                    if(["finalized"].includes(this.forms.entity.createUpdate.extras.modals.default.type)) {

                        this.forms.entity.createUpdate.data.branch     = null;
                        this.forms.entity.createUpdate.data.start_date = "";

                    }
                    break;

                case "modalQrCameraEntity":
                    break;

                case "onResultQrCamera":
                    this.forms.entity.qrCamera.data.customers = [];
                    break;

                case "qrCameraEntity":
                    // this.forms.entity.qrCamera.data.id        = null;
                    this.forms.entity.qrCamera.data.customers = [];
                    // this.forms.entity.qrCamera.data.end_date  = "";
                    // this.forms.entity.qrCamera.data.status    = null;

                    // if(["finalized"].includes(this.forms.entity.qrCamera.extras.modals.default.type)) {

                        // this.forms.entity.qrCamera.data.branch     = null;
                        // this.forms.entity.qrCamera.data.start_date = "";

                    // }
                    break;

                case "modalQrScannerEntity":
                    break;

                case "onResultQrScanner":
                    this.forms.entity.qrScanner.data.code = "";
                    this.forms.entity.qrScanner.data.customers = [];
                    break;

                case "qrScannerEntity":
                    // this.forms.entity.qrScanner.data.id        = null;
                    this.forms.entity.qrScanner.data.customers = [];
                    // this.forms.entity.qrScanner.data.end_date  = "";
                    // this.forms.entity.qrScanner.data.status    = null;

                    // if(["finalized"].includes(this.forms.entity.qrScanner.extras.modals.default.type)) {

                        // this.forms.entity.qrScanner.data.branch     = null;
                        // this.forms.entity.qrScanner.data.start_date = "";

                    // }
                    break;
            }

        },
        formErrors({functionName, type = "clear", errors = []}) {

            if(["modalCreateUpdateEntity", "createUpdateEntity"].includes(functionName)) {

                this.forms.entity.createUpdate.errors = ["set"].includes(type) ? errors : [];

            }else if(["modalQrCameraEntity", "qrCameraEntity"].includes(functionName)) {

                this.forms.entity.qrCamera.errors = ["set"].includes(type) ? errors : [];

            }else if(["modalQrScannerEntity", "qrScannerEntity"].includes(functionName)) {

                this.forms.entity.qrCamera.errors = ["set"].includes(type) ? errors : [];

            }

        },
        validateForm({functionName, form = null, extras = null}) {

            let result = {
                bool: true
            };

            if(["createUpdateEntity"].includes(functionName)) {

                result.id         = [];
                result.branch     = [];
                result.customer   = [];
                result.start_date = [];
                result.end_date   = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                const isFinalized = ["finalized"].includes(extras?.modal?.type);

                if(isFinalized) {

                    if(!this.isDefined({value: form?.id})) {

                        result.id.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

                if(!this.isDefined({value: form?.branch})) {

                    result.branch.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.customer})) {

                    result.customer.push(`${isDescriptive ? "Cliente:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.start_date})) {

                    result.start_date.push(`${isDescriptive ? "Ingreso:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(isFinalized) {

                    if(!this.isDefined({value: form?.end_date})) {

                        result.end_date.push(`${isDescriptive ? "Salida:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

            }else if(["qrCameraEntity"].includes(functionName)) {

                result.id         = [];
                result.branch     = [];
                result.customers  = [];
                result.start_date = [];
                result.end_date   = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                const isFinalized = ["finalized"].includes(extras?.modal?.type);

                if(isFinalized) {

                    if(!this.isDefined({value: form?.id})) {

                        result.id.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

                if(!this.isDefined({value: form?.branch})) {

                    result.branch.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.customers}) || (form?.customers).length === 0) {

                    result.customers.push(`${isDescriptive ? "Clientes:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.start_date})) {

                    result.start_date.push(`${isDescriptive ? "Ingreso:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(isFinalized) {

                    if(!this.isDefined({value: form?.end_date})) {

                        result.end_date.push(`${isDescriptive ? "Salida:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

            }else if(["cancelEntity"].includes(functionName)) {

                result.msg = [];

                if(!this.isDefined({value: form?.id})) {

                    result.msg.push(`Registro: ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

            }

            return result;

        },
        // Others
        getAttendanceDeviceLabel(record) {

            const device = record?.biometric_device || record?.biometricDevice;
            const source = record?.source || record?.formatted_source;

            if(this.isDefined({value: device?.name})) return `Dispositivo: ${device.name}`;
            if(this.isDefined({value: source})) return `Origen: ${source}`;

            return "Registro manual";

        },
        latestAttendanceCorrection(record) {

            return (record?.corrections ?? [])[0] ?? null;

        },
        latestAttendanceCorrectionLabel(record) {

            const correction = this.latestAttendanceCorrection(record);
            const status = correction?.status ?? "pending";

            const labels = {
                pending: "Corrección pendiente",
                approved: "Corrección aprobada",
                rejected: "Corrección rechazada"
            };

            return labels[status] ?? "Corrección registrada";

        },
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        }
    },
    computed: {
        breadcrumbTitles: function() {

            return [{title: "Seguimiento"}, this.config.entity.page];

        },
        branches: function() {

            return this.options?.branches?.records.map(e => ({code: e.id, label: e.name, data: e}));

        },
        customers: function() {

            return this.options?.customers?.records.map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

        },
        formModes: function() {

            return [
                {code: "manual", label: "Manual", label_sm: "Manual", icon: "fa-hand"},
                {code: "qrCamera", label: "Cámara interna", label_sm: "Cámara", icon: "fa-camera"},
                {code: "qrScanner", label: "Escáner externo", label_sm: "Escáner", icon: "fa-qrcode"}
            ];

        },
        qrCameraModes: function() {

            return [
                {code: "carnet", label: "Carnet", tooltip: "Carnet generado por el sistema con código QR.", icon: "fa fa-id-badge", color: "primary"},
                {code: "document_number", label: "Documento", tooltip: "Escanea o escribe el número de documento del cliente.", icon: "fa fa-id-card", color: "info"}
            ];

        }
    },
    watch: {
        "lists.entity.filters.branch": function(newValue, oldValue) {

            this.listEntity({});

        },
        "lists.entity.filters.customer": function(newValue, oldValue) {

            this.listEntity({});

        }
    }
};
</script>
