<template>
    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2">
        <div class="row g-3 g-md-3 w-100">
            <slot name="statisticsPrepend"></slot>
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12" v-if="(options?.information ?? []).some(e => ['sales'].includes(e))">
                <div class="card d-flex justify-content-start align-items-center shadow h-100">
                    <div class="card-header py-2 border-bottom border-primary">
                        <span class="fw-bold text-uppercase text-primary">💰 Ventas</span>
                    </div>
                    <div class="card-body w-100 py-0">
                        <ul class="py-2">
                            <li v-for="(sta, indexSta) in salesStatistics" :key="indexSta">
                                <span v-text="sta?.label" class="colon-at-end"></span>
                                <span v-text="formatStatisticValue(sta)" class="ms-2 fw-semibold"></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12" v-if="(options?.information ?? []).some(e => ['attendances'].includes(e))">
                <div class="card d-flex justify-content-start align-items-center shadow h-100">
                    <div class="card-header py-2 border-bottom border-secondary">
                        <span class="fw-bold text-uppercase text-secondary">⌚ Asistencias</span>
                    </div>
                    <div class="card-body w-100 py-0">
                        <ul class="py-2">
                            <li v-for="(sta, indexSta) in attendancesStatistics" :key="indexSta">
                                <span v-text="sta?.label" class="colon-at-end"></span>
                                <span v-text="formatStatisticValue(sta)" class="ms-2 fw-semibold"></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="resume.length > 0" class="w-100">
            <div class="d-flex justify-content-start align-items-center w-100 mb-1">
                <div class="divider">
                    <div class="divider-text">
                        <span class="ms-2 fw-bold colon-at-end text-primary">Detalle</span>
                    </div>
                </div>
            </div>
            <ul class="timeline d-flex flex-wrap gap-3 gap-md-2">
                <li :class="['timeline-item', 'ps-4', 'w-100']" v-for="(item, indexItem) in resume" :key="indexItem">
                    <span :class="['timeline-point', 'timeline-point-'+item?.class]"></span>
                    <template v-if="['sales'].includes(item?.type)">
                        <div class="timeline-event shadow-sm">
                            <div class="timeline-header d-flex justify-content-between align-items-center flex-wrap mb-2 mb-md-1">
                                <div class="d-flex justify-content-start align-items-start flex-wrap gap-3">
                                    <div>
                                        <span :class="['text-uppercase', 'fw-bold']" v-text="item?.name"></span>
                                    </div>
                                    <StatusBadge
                                        class="py-1"
                                        :status="item?.data?.status"
                                        :formatted-status="item?.data?.formatted_status"/>
                                </div>
                                <small class="text-body-secondary" v-text="legibleFormatDate({dateString: item?.data?.created_at, type: 'datetime'})"></small>
                            </div>
                            <div class="d-flex justify-content-start align-items-end flex-wrap gap-1">
                                <div class="d-flex flex-wrap">
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Sucursal</span>
                                        <span v-text="item?.data?.serie?.branch?.name" class="fw-semibold ms-2"></span>
                                    </div>
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Documento</span>
                                        <span v-text="item?.data?.serie_sequential" class="fw-semibold ms-2"></span>
                                        <i class="fa fa-info-circle ms-2 cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" :title="item.data?.serie?.document_type?.name"></i>
                                    </div>
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Fecha de emisión</span>
                                        <span v-text="legibleFormatDate({dateString: item?.data?.issue_date, type: 'date'})" class="fw-semibold ms-2"></span>
                                    </div>
                                </div>
                                <div class="ms-auto" v-if="isDefined({value: item?.data?.total})">
                                    <div class="d-block w-100 fw-bold">
                                        <span v-text="item?.icon"></span>
                                        <span v-text="item?.data?.currency?.sign+' '+item?.data?.total" class="ms-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else-if="['subscriptions'].includes(item?.type)">
                        <div class="timeline-event shadow-sm">
                            <div class="timeline-header d-flex justify-content-between align-items-center flex-wrap mb-2 mb-md-1">
                                <div class="d-flex justify-content-start align-items-start flex-wrap gap-3">
                                    <div>
                                        <span :class="['text-uppercase', 'fw-bold']" v-text="item?.name"></span>
                                    </div>
                                    <StatusBadge
                                        class="py-1"
                                        :status="item?.data?.status"
                                        :formatted-status="item?.data?.formatted_status"
                                        :custom-variants="statusBadgeSubscriptionVariants"/>
                                </div>
                                <small class="text-body-secondary" v-text="legibleFormatDate({dateString: item?.data?.created_at, type: 'datetime'})"></small>
                            </div>
                            <div class="d-flex justify-content-start align-items-end flex-wrap gap-1">
                                <div class="d-flex flex-wrap">
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Sucursal</span>
                                        <span v-text="item?.data?.branch?.name" class="fw-semibold ms-2"></span>
                                    </div>
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Fecha de inicio</span>
                                        <span v-text="legibleFormatDate({dateString: item?.data?.start_date, type: 'datetime'})" class="fw-semibold ms-2"></span>
                                    </div>
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Fecha de finalización</span>
                                        <span v-text="legibleFormatDate({dateString: item?.data?.end_date, type: 'datetime'})" class="fw-semibold ms-2"></span>
                                    </div>
                                </div>
                                <div class="ms-auto" v-if="isDefined({value: item?.data?.formatted_duration})">
                                    <div class="d-block w-100 fw-bold">
                                        <span v-text="item?.icon"></span>
                                        <span v-text="item?.data?.formatted_duration" class="ms-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else-if="['attendances'].includes(item?.type)">
                        <div class="timeline-event shadow-sm">
                            <div class="timeline-header d-flex justify-content-between align-items-center flex-wrap mb-2 mb-md-1">
                                <div class="d-flex justify-content-start align-items-start flex-wrap gap-3">
                                    <div>
                                        <span :class="['text-uppercase', 'fw-bold']" v-text="item?.name"></span>
                                    </div>
                                    <StatusBadge
                                        class="py-1"
                                        :status="item?.data?.status"
                                        :formatted-status="item?.data?.formatted_status"
                                        :custom-variants="statusBadgeAttendanceTimelineVariants"/>
                                </div>
                                <small class="text-body-secondary" v-text="legibleFormatDate({dateString: item?.data?.created_at, type: 'datetime'})"></small>
                            </div>
                            <div class="d-flex justify-content-start align-items-end flex-wrap gap-1">
                                <div class="d-flex flex-wrap">
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Sucursal</span>
                                        <span v-text="item?.data?.branch?.name" class="fw-semibold ms-2"></span>
                                    </div>
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Ingreso</span>
                                        <span v-text="legibleFormatDate({dateString: item?.data?.start_date, type: 'datetime'})" class="fw-semibold ms-2"></span>
                                    </div>
                                    <div class="d-block w-100">
                                        <span class="colon-at-end small">Salida</span>
                                        <span v-text="isDefined({value: item?.data?.end_date}) ? legibleFormatDate({dateString: item?.data?.end_date, type: 'datetime'}) : 'Pendiente'" class="fw-semibold ms-2"></span>
                                    </div>
                                </div>
                                <div class="ms-auto" v-if="isDefined({value: item?.data?.end_date})">
                                    <div class="d-block w-100 fw-bold">
                                        <span v-text="item?.icon"></span>
                                        <span v-text="separatorNumber(item?.data?.worked_hours)" class="ms-2"></span>
                                        <span v-text="item?.data?.worked_hours == 1 ? 'hora trabajada' : 'horas trabajadas'" class="ms-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </li>
            </ul>
        </div>
        <div v-else class="text-center">
            <WithoutData type="image"/>
        </div>
    </div>
</template>

<script>
import * as Utils  from "../../Helpers/Utils.js";
import {STATUS_BADGE_CUSTOM_SUBSCRIPTION, STATUS_BADGE_CUSTOM_ATTENDANCE_TIMELINE} from "@System/Helpers/ModuleConstants.js";

export default {
    name: "Timeline",
    emits: [],
    props: {
        data: {
            type: Object,
            required: false,
            default: {} // customer, sales, subscriptions, attendances
        }
    },
    computed: {
        resume: function() {

            const sales = (this.data?.sales ?? []).map(e => ({
                type: "sales",
                name: "Venta",
                date: new Date(e?.created_at),
                data: e,
                icon: "💰",
                class: "primary"
            }));

            const subscriptions = (this.data?.subscriptions ?? []).map(e => ({
                type: "subscriptions",
                name: "Membresía",
                date: new Date(e?.created_at),
                data: e,
                icon: "📄",
                class: "success"
            }));

            const attendances = (this.data?.attendances ?? []).map(e => ({
                type: "attendances",
                name: "Asistencia",
                date: new Date(e?.created_at),
                data: e,
                icon: "⌚",
                class: "secondary"
            }));

            const priority = {
                sale: 3,
                subscription: 2,
                attendance: 1
            };

            return [...sales, ...subscriptions, ...attendances].sort((a, b) => {

                const dateDiff = b.date - a.date;

                if(dateDiff !== 0) return dateDiff;

                return priority[a.type] - priority[b.type];

            });

        },
        salesStatistics() {

            const data  = (this.data?.sales ?? []).filter(e => !["inactive", "canceled"].includes(e.status));
            const count = data.length;

            const total = data.reduce((sum, item) => sum + parseFloat(item?.total), 0);
            const currency = count > 0 ? data[0]?.currency : null;

            let statistics = [
                {label: "Cantidad", value: count, type: "number"},
                {label: "Total", value: total, currency, type: "currency"},
                {label: "Sede más frecuente", value: this.getMostFrequentSerie(data)?.branch?.name ?? "-", type: "value"}
            ];

            for(let sta of statistics) {

                if(["number"].includes(sta?.type)) {

                    sta.parseValue = this.separatorNumber(sta.value);

                }else if(["currency"].includes(sta?.type)) {

                    sta.parseValue = this.separatorNumber(sta.value);
                    sta.sign = currency?.sign;

                }

            }

            return statistics;

        },
        attendancesStatistics() {

            const data  = (this.data?.attendances ?? []).filter(e => !["inactive", "canceled"].includes(e.status));
            const count = data.length;

            const total = data.reduce((sum, item) => sum + parseFloat(item?.worked_hours), 0);
            const units = {singular: "Hora", plural: "Horas"};

            let statistics = [
                {label: "Cantidad", value: count, type: "number"},
                {label: "Horas", value: total, units, type: "units"},
                {label: "Promedio de horas", value: count === 0 ? 0 : total / count, units, type: "units"}
            ];

            for(let sta of statistics) {

                if(["number"].includes(sta?.type)) {

                    sta.parseValue = this.separatorNumber(sta.value);

                }else if(["units"].includes(sta?.type)) {

                    sta.parseValue = this.separatorNumber(sta.value);
                    sta.unitValue = sta.value == 1 ? sta?.units?.singular : sta?.units?.plural;

                }

            }

            return statistics;

        },
        options() {

            return this.data?.extras?.options;

        },
        statusBadgeSubscriptionVariants() {
            return STATUS_BADGE_CUSTOM_SUBSCRIPTION;
        },
        statusBadgeAttendanceTimelineVariants() {
            return STATUS_BADGE_CUSTOM_ATTENDANCE_TIMELINE;
        }
    },
    methods: {
        formatStatisticValue(sta) {

            if(!sta) return "";
            if(sta.type === "number") return sta.parseValue;
            if(sta.type === "currency") return `${sta.sign ?? ''} ${sta.parseValue}`;
            if(sta.type === "units") return `${sta.parseValue ?? ''} ${sta.unitValue}`;

            return sta.value;

        },
        getMostFrequentSerie(sales) {

            let branches = {};

            sales.forEach(e => {

                let code = e.serie?.branch?.id;

                if(code !== undefined && code !== null) {

                    branches[code] = (branches[code] || 0) + 1;

                }

            });

            // Information
            let maxCount = 0;
            let mostFrequent = null;

            for(const code in branches) {

                if(branches[code] > maxCount) {

                    maxCount = branches[code];
                    mostFrequent = code;

                }

            }

            let branch = sales.find(e => e.serie?.branch?.id == mostFrequent)?.serie?.branch;

            return {
                branch,
                count: maxCount
            };

        },
        // Others
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        }
    }
};
</script>
