<template>
    <header class="fg-page-header">
        <div>
            <h1 class="fg-title">Resumen del negocio</h1>
            <p class="fg-help">Consulta los indicadores de la operación por fecha y sucursal.</p>
        </div>
    </header>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading"/>
        <div v-else class="fg-page-content">
            <FgEmptyState v-if="initFailed" title="No se pudieron cargar los filtros"
                description="Vuelve a intentar para consultar los indicadores.">
                <FgButton tone="secondary" @click="initialize">Volver a intentar</FgButton>
            </FgEmptyState>
            <form v-else class="fg-filters fg-filter-grid fg-dashboard__filters" role="search" aria-label="Filtrar resumen del negocio"
                @submit.prevent="applyFilters">
                <FgField label="Fecha" class="fg-filter-grid__field" v-slot="{id}">
                    <div class="fg-field__control">
                        <input :id="id" v-model="draftFilters.date" type="date" class="fg-input"
                            :max="dashboardConsultDateMax" :disabled="loading" required/>
                    </div>
                </FgField>
                <FgField label="Sucursal" class="fg-filter-grid__field">
                    <FgSelect v-model="draftFilters.branch_id" :options="branchOptions" :reduce="branch => branch.id"
                        :searchable="branchOptions.length > 6" :clearable="false" :disabled="loading"/>
                </FgField>
                <div class="fg-filter-grid__actions">
                    <FgFilterActions submit :disabled="loading" :loading="loading" @clear="clearFilters"/>
                </div>
            </form>
            <FgListLoading v-if="loading" class="mt-3" label="Consultando indicadores…"/>
            <FgEmptyState v-else-if="queryFailed" class="mt-3" title="No se pudieron consultar los indicadores"
                :description="queryFailureMessage">
                <FgButton tone="secondary" @click="initData">Volver a intentar</FgButton>
            </FgEmptyState>
            <div v-else-if="appliedFilters && !initFailed" class="fg-dashboard mt-3">
                <p v-if="appliedFiltersLabel" class="fg-help mb-2">{{ appliedFiltersLabel }}</p>
                <div class="row mb-4 align-items-start fg-dashboard__split">
                    <div class="col-12">
                        <section class="fg-dashboard__kpis fg-dashboard__kpis--split row g-2 mb-0" :aria-label="MODULE.texts.chart.kpiSectionAria">
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="fg-dashboard-kpi fg-dashboard-kpi--success h-100">
                                    <div class="fg-dashboard-kpi__inner">
                                        <div class="fg-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-sack-dollar"></i>
                                        </div>
                                        <div class="fg-dashboard-kpi__body">
                                            <p class="fg-dashboard-kpi__label" v-text="MODULE.texts.kpi.salesTotal"></p>
                                            <p class="fg-dashboard-kpi__value" v-text="formatCurrencyTotals(forms.entity.dashboard.data.sales?.net?.amounts)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="fg-dashboard-kpi fg-dashboard-kpi--danger h-100">
                                    <div class="fg-dashboard-kpi__inner">
                                        <div class="fg-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-sack-xmark"></i>
                                        </div>
                                        <div class="fg-dashboard-kpi__body">
                                            <p class="fg-dashboard-kpi__label" v-text="MODULE.texts.kpi.salesCanceled"></p>
                                            <p class="fg-dashboard-kpi__value" v-text="formatCurrencyTotals(forms.entity.dashboard.data.sales?.canceled?.amounts)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="fg-dashboard-kpi fg-dashboard-kpi--info h-100">
                                    <div class="fg-dashboard-kpi__inner">
                                        <div class="fg-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-clipboard-user"></i>
                                        </div>
                                        <div class="fg-dashboard-kpi__body">
                                            <p class="fg-dashboard-kpi__label" v-text="MODULE.texts.kpi.attendancesToday"></p>
                                            <p class="fg-dashboard-kpi__value" v-text="separatorNumber(forms.entity.dashboard.data.attendances?.count ?? 0, 0)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="fg-dashboard-kpi fg-dashboard-kpi--primary h-100">
                                    <div class="fg-dashboard-kpi__inner">
                                        <div class="fg-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </div>
                                        <div class="fg-dashboard-kpi__body">
                                            <p class="fg-dashboard-kpi__label" v-text="MODULE.texts.kpi.expiringSubscriptions"></p>
                                            <p class="fg-dashboard-kpi__value" v-text="separatorNumber(forms.entity.dashboard.data.expiringSubscriptions?.count ?? 0, 0)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="fg-dashboard-kpi fg-dashboard-kpi--warning h-100">
                                    <div class="fg-dashboard-kpi__inner">
                                        <div class="fg-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-building-user"></i>
                                        </div>
                                        <div class="fg-dashboard-kpi__body">
                                            <p class="fg-dashboard-kpi__label" v-text="MODULE.texts.kpi.branchesActive"></p>
                                            <p class="fg-dashboard-kpi__value" v-text="separatorNumber(forms.entity.dashboard.data.branches?.active_count ?? 0, 0)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="row g-3 mb-4 pt-2" aria-labelledby="fg-dashboard-chart-title">
                    <div class="col-12">
                        <div class="fg-dashboard-chart-section__title-wrap">
                            <h2 id="fg-dashboard-chart-title" class="fg-dashboard-chart-section__title" v-text="MODULE.texts.chart.sectionTitle"></h2>
                            <p class="fg-dashboard-chart-section__subtitle" v-text="dashboardChartHoursRangeCaption"></p>
                            <p v-if="!dashboardChartNoSales" class="fg-dashboard-chart-scroll-hint" v-text="MODULE.texts.chart.scrollHint"></p>
                        </div>
                        <div class="fg-dashboard-chart-panel">
                            <div
                                v-if="!dashboardChartNoSales"
                                class="fg-dashboard-chart-scroll"
                                role="region"
                                :aria-label="MODULE.texts.chart.chartScrollRegionAria">
                                <div
                                    class="fg-dashboard-chart-scroll-inner fg-dashboard-chart-wrap fg-dashboard-chart-wrap--hourly"
                                    :style="dashboardChartScrollInnerStyle">
                                    <canvas
                                        id="dashboardSalesHourlyChart"
                                        class="chartjs fg-dashboard-chart-canvas"
                                        data-height="300"
                                        :aria-label="MODULE.texts.chart.canvasAria"
                                        role="img"></canvas>
                                </div>
                            </div>
                            <div
                                v-else
                                class="fg-dashboard-chart-wrap fg-dashboard-chart-wrap--hourly fg-dashboard-chart-wrap--hourly-empty">
                                <div
                                    class="fg-dashboard-chart-empty-overlay"
                                    role="status"
                                    aria-live="polite">
                                    <span class="fg-dashboard-chart-empty-overlay__text" v-text="MODULE.texts.chart.noSales"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </InitialLoadTransition>
</template>

<script>
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import {showFiltersCleared} from "@System/Helpers/Alerts.js";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";

const MODULE_CONFIG = {
    entity: "dashboard",
    menuId: "menu-parent-analytics",
    pageTitle: "Resumen del negocio",
    breadcrumbParent: "Analítica"
};

const TEXTS = {
    kpi: {
        salesTotal: "Ventas netas",
        salesCanceled: "Ventas anuladas",
        attendancesToday: "Asistencias del día",
        expiringSubscriptions: "Membresías por vencer",
        branchesActive: "Sucursales activas"
    },
    chart: {
        sectionTitle: "Ventas por hora",
        scrollHint: "Desliza horizontalmente para ver todas las horas.",
        noSales: "SIN VENTAS",
        hoursRangeAllDay: "Todas las horas del día",
        datasetLegend: "Total por hora (S/)",
        yAxisTitle: "Soles (S/)",
        tooltipSalesPrefix: "Ventas: S/ ",
        kpiSectionAria: "Indicadores del día consultado",
        chartScrollRegionAria: "Gráfico de ventas por hora, desplazable en pantallas pequeñas",
        canvasAria: "Gráfico de barras: ventas por hora del día consultado"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    texts: TEXTS
};

const ALL_BRANCHES = "all";
let dashboardSalesChartInstance = null;

export default {
    mixins: [InitialPageMixin],
    name: "DashboardMain",
    components: {FgButton, FgEmptyState, FgField, FgFilterActions, FgListLoading, FgSelect},
    data() {
        return {
            forms: {
                entity: {
                    dashboard: {
                        data: {
                            sales: null,
                            attendances: null,
                            expiringSubscriptions: null,
                            branches: null,
                            users: null,
                            dashboardChartMinWidthPx: 0 // Minimum scroll area width for hourly chart (per bar; wider for gap ranges)
                        }
                    }
                }
            },
            draftFilters: {date: Utils.getCurrentDate(), branch_id: ALL_BRANCHES},
            appliedFilters: null,
            branchOptions: [],
            initFailed: false,
            loading: false,
            queryFailed: false,
            queryFailureMessage: "Intenta consultar nuevamente.",
            MODULE,
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: MODULE_CONFIG.entity}),
                    page: {
                        title: MODULE_CONFIG.pageTitle,
                        active: true,
                        menu: {
                            id: MODULE_CONFIG.menuId
                        }
                    }
                }
            }
        };
    },
    mounted() {

        navbarItem(this.config.entity.page.menu.id, {});

        this.initialize();

        this._onDashboardChartResize = () => this.onDashboardChartWindowResize();

        window.addEventListener("resize", this._onDashboardChartResize);

    },
    beforeUnmount() {

        if(this._onDashboardChartResize) window.removeEventListener("resize", this._onDashboardChartResize);
        this.dashboardChartDestroySalesHourlyIfAny();

    },
    methods: {
        async initialize() {
            this.initialLoading = true;
            this.initFailed = false;
            const ready = await this.loadInitialParams(() => this.initParams());

            if(ready) this.applyFilters();
        },
        async initParams() {
            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"}
            });

            this.initFailed = !Requests.valid({result: response});
            if(this.initFailed) return false;

            this.branchOptions = [
                {id: ALL_BRANCHES, label: "Todas las sucursales permitidas"},
                ...(response.data.branches ?? []).map(branch => ({
                    id: branch.id,
                    label: branch.status === "active" ? branch.name : `${branch.name} (inactiva)`
                }))
            ];

            return true;
        },
        clearFilters() {
            this.draftFilters = {date: Utils.getCurrentDate(), branch_id: ALL_BRANCHES};
            showFiltersCleared();
        },
        applyFilters() {
            if(this.loading || !this.draftFilters.date) return;

            this.appliedFilters = {...this.draftFilters};
            return this.initData();
        },
        async initData() {
            if(this.loading || !this.appliedFilters) return;

            const {date, branch_id} = this.appliedFilters;
            this.loading = true;
            this.queryFailed = false;
            this.dashboardChartDestroySalesHourlyIfAny();

            const response = await Requests.get({
                route: this.routeActions.initData,
                data: {page: "main", date, branch_id: branch_id === ALL_BRANCHES ? null : branch_id}
            });

            this.loading = false;
            this.queryFailed = !Requests.valid({result: response});
            if(this.queryFailed) {
                this.queryFailureMessage = response.code === 403
                    ? "Tu perfil no tiene acceso a esta consulta o a la sucursal seleccionada."
                    : "Intenta consultar nuevamente.";
                return;
            }

            Object.assign(this.forms.entity.dashboard.data, response.data.data);
            this.$nextTick(() => this.initChart());
        },
        // X-axis label: 12 h time with a. m. / p. m. (hour index 0-23)
        dashboardChartFormatHourLabelAmpm(h) {

            if(h === 0) return "12:00 a. m.";
            if(h === 12) return "12:00 p. m.";
            if(h < 12) return `${h}:00 a. m.`;

            return `${h - 12}:00 p. m.`;

        },
        // End of hour range for tooltip (...:59)
        dashboardChartFormatHourEndAmpm(h) {

            if(h === 0) return "12:59 a. m.";
            if(h === 12) return "12:59 p. m.";
            if(h < 12) return `${h}:59 a. m.`;

            return `${h - 12}:59 p. m.`;

        },
        // Compact X-axis label (wider bars, no tilt): "9 a. m.", "12 p. m."
        dashboardChartFormatHourCompactAmpm(h) {

            if(h === 0) return "12 a. m.";
            if(h === 12) return "12 p. m.";
            if(h < 12) return `${h} a. m.`;

            return `${h - 12} p. m.`;

        },
        // Y-axis title "Soles (S/)": show only from md breakpoint up (Bootstrap); frees space on mobile
        dashboardChartShouldShowYAxisTitle() {

            if(typeof window === "undefined") return true;

            return window.matchMedia("(min-width: 768px)").matches;

        },
        onDashboardChartWindowResize() {

            if(!dashboardSalesChartInstance) return;

            const show = this.dashboardChartShouldShowYAxisTitle();
            const yTitle = dashboardSalesChartInstance.options?.scales?.y?.title;

            if(!yTitle) return;
            if(yTitle.display === show) return;

            yTitle.display = show;

            dashboardSalesChartInstance.update("none");

        },
        // Y-axis max from peak (~12% headroom; 1–2–5–10 rounding)
        dashboardChartNiceCeilAxisMax(peak) {

            const headroom = 1.12;

            if(peak <= 0) return 10;

            const target = peak * headroom;

            if(target < 1) return Math.max(1, Math.ceil(target * 100) / 100);

            const exp = Math.floor(Math.log10(target));
            const pow10 = Math.pow(10, exp);
            const n = target / pow10;
            let nice;

            if(n <= 1) nice = 1;
            else if(n <= 2) nice = 2;
            else if(n <= 5) nice = 5;
            else nice = 10;

            return nice * pow10;

        },
        dashboardChartSyncChartjsCanvasHeights() {

            document.querySelectorAll(".chartjs").forEach((el) => {

                el.height = el.dataset.height;

            });

        },
        dashboardChartTotalsByHour(records) {

            const totalsByHour = Array.from({length: 24}, () => 0);

            records.forEach((sale) => {

                const saleHour = new Date(sale.created_at).getHours();

                if(saleHour >= 0 && saleHour < 24) totalsByHour[saleHour] = Utils.sum([totalsByHour[saleHour], sale.total]);

            });

            return totalsByHour;

        },
        dashboardChartSliceHours(totalsByHour) {

            const hasAnySale = totalsByHour.some((t) => t > 0);
            let firstHour = 0;
            let lastHour = 23;

            if(hasAnySale) {

                firstHour = totalsByHour.findIndex((t) => t > 0);

                for(let i = 23; i >= 0; i--) {

                    if(totalsByHour[i] > 0) {

                        lastHour = i;
                        break;

                    }

                }

            }

            const sliceHours = [];

            for(let h = firstHour; h <= lastHour; h++) {

                sliceHours.push(h);

            }

            return sliceHours;

        },
        // Merge consecutive no-sales hours into one X-axis category to save space. Hours with sales stay one bar per hour.
        dashboardChartBuildSegments(sliceHours, totalsByHour) {

            const segments = [];
            let si = 0;

            while(si < sliceHours.length) {

                const h = sliceHours[si];

                if(totalsByHour[h] > 0) {

                    segments.push({kind: "single", hour: h});
                    si++;

                }else {

                    const zStart = h;
                    let zEnd = h;

                    while(si < sliceHours.length && totalsByHour[sliceHours[si]] === 0) {

                        zEnd = sliceHours[si];
                        si++;

                    }

                    zStart === zEnd ? segments.push({kind: "single", hour: zStart}) : segments.push({kind: "gap", startHour: zStart, endHour: zEnd});

                }

            }

            return segments;

        },
        dashboardChartLabelsAndData(segments, totalsByHour) {

            const labels = segments.map((seg) => {

                if(seg.kind === "single") return this.dashboardChartFormatHourCompactAmpm(seg.hour);

                return `${this.dashboardChartFormatHourCompactAmpm(seg.startHour)} - ${this.dashboardChartFormatHourCompactAmpm(seg.endHour)}`;

            });

            const data = segments.map((seg) => {

                if(seg.kind === "single") return totalsByHour[seg.hour];

                return Utils.sum(totalsByHour.slice(seg.startHour, seg.endHour + 1));

            });

            const segmentHasSales = data.map((v) => Number(v) > 0);

            return {labels, data, segmentHasSales};

        },
        // Y-axis: 7 horizontal intervals → 8 ticks (0 … max), integers with fixed step
        dashboardChartYAxisStepAndMax(maxVal) {

            const yMax = this.dashboardChartNiceCeilAxisMax(maxVal);
            const yIntervals = 7;
            let yStep = Math.max(1, Math.ceil(yMax / yIntervals));
            let yAxisMax = yStep * yIntervals;

            while(yAxisMax < yMax) {

                yStep += 1;
                yAxisMax = yStep * yIntervals;

            }

            return {yStep, yAxisMax};

        },
        dashboardChartBarLayout(nBars) {

            return {
                maxBarThickness: nBars <= 6 ? 52 : nBars <= 12 ? 42 : nBars <= 18 ? 34 : 26,
                categoryPercentage: nBars <= 10 ? 0.94 : nBars <= 16 ? 0.9 : 0.86,
                barPercentage: nBars <= 10 ? 0.98 : 0.95,
                labelFontSize: nBars > 18 ? 7.5 : nBars > 14 ? 8 : 9.5,
                tickRotation: nBars > 16 ? 35 : 0
            };

        },
        // No-sales ranges: extra width for long labels (e.g. 9 a. m. – 11 a. m.)
        dashboardChartScrollMinWidthForSegments(segments) {

            const viewportNarrow = typeof window !== "undefined" && window.matchMedia("(max-width: 767.98px)").matches;
            const pxPerSingle = viewportNarrow ? 52 : 44;
            const pxPerGap = viewportNarrow ? 118 : 88;
            let scrollMinPx = 48;

            segments.forEach((seg) => {

                scrollMinPx += seg.kind === "gap" ? pxPerGap : pxPerSingle;

            });

            return Math.max(320, scrollMinPx);

        },
        dashboardChartMakeTickIndexResolver(labels) {

            return (ctx) => {

                if(typeof ctx.index === "number") return ctx.index;

                const t = ctx.tick;

                if(t && typeof t.index === "number") return t.index;

                if(t && typeof t.label === "string") {

                    let j = labels.indexOf(t.label);

                    if(j >= 0) return j;

                    j = labels.findIndex((lb) => String(lb) === String(t.label));

                    if(j >= 0) return j;

                }

                return -1;

            };

        },
        dashboardChartBarIndexStyles(data, primary, barFill, barHover) {

            const transparent = "rgba(0, 0, 0, 0)";

            return {
                barBgPerIndex: data.map((v) => (Number(v) > 0 ? barFill : transparent)),
                barBorderPerIndex: data.map((v) => (Number(v) > 0 ? primary : transparent)),
                barBorderWPerIndex: data.map((v) => (Number(v) > 0 ? 1 : 0)),
                barHoverBgPerIndex: data.map((v) => (Number(v) > 0 ? barHover : transparent)),
                barHoverBorderPerIndex: data.map((v) => (Number(v) > 0 ? primary : transparent))
            };

        },
        // Vertical watermark for categories with no sales
        dashboardChartNoSalesWatermarkPlugin(segmentHasSales) {

            const watermarkText = this.MODULE.texts.chart.noSales;

            return {
                id: "dashboardEmptyWatermark",
                afterDatasetsDraw(chart) {

                    const meta = chart.getDatasetMeta(0);

                    if(!meta || !meta.data) return;

                    const values = chart.data.datasets[0].data;
                    const { ctx, chartArea } = chart;
                    let fontFamily = "sans-serif";

                    if(typeof Chart !== "undefined" && Chart.defaults && Chart.defaults.font && Chart.defaults.font.family) fontFamily = Chart.defaults.font.family;

                    ctx.save();
                    const plotH = chartArea.bottom - chartArea.top;
                    const n = values.length;

                    values.forEach((_, i) => {

                        if(segmentHasSales[i]) return;

                        const el = meta.data[i];

                        if(!el || el.skip) return;

                        const x = el.x;
                        const y = (chartArea.top + chartArea.bottom) / 2;
                        let fontSize = Math.min(11, Math.max(8, 7 + chartArea.width / Math.max(n * 6, 1)));

                        ctx.font = `600 ${fontSize}px ${fontFamily}`;

                        let textW = ctx.measureText(watermarkText).width;

                        while(textW > plotH - 12 && fontSize > 7) {

                            fontSize -= 0.5;
                            ctx.font = `600 ${fontSize}px ${fontFamily}`;
                            textW = ctx.measureText(watermarkText).width;

                        }

                        ctx.save();
                        ctx.translate(x, y);

                        // Vertical text (-90°): narrow on X-axis, avoids overlapping adjacent categories
                        ctx.rotate(-Math.PI / 2);
                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";
                        ctx.fillStyle = "rgba(148, 163, 184, 0.44)";
                        ctx.fillText(watermarkText, 0, 0);
                        ctx.restore();

                    });

                    ctx.restore();

                }
            };

        },
        dashboardChartDestroySalesHourlyIfAny() {

            if(dashboardSalesChartInstance) {

                dashboardSalesChartInstance.destroy();
                dashboardSalesChartInstance = null;

            }

        },
        /**
         * Full Chart.js config for hourly sales bar chart.
         * @param {object} p — labels, data, segments, segmentHasSales, chartGridColor, layout + bar index styles + yAxisMax, yStep
         */
        dashboardChartSalesHourlyConfig(p) {

            const vm = this;
            const {
                labels,
                data,
                segments,
                segmentHasSales,
                chartGridColor,
                maxBarThickness,
                categoryPercentage,
                barPercentage,
                labelFontSize,
                tickRotation,
                yAxisMax,
                yStep,
                barBgPerIndex,
                barBorderPerIndex,
                barBorderWPerIndex,
                barHoverBgPerIndex,
                barHoverBorderPerIndex
            } = p;

            const resolveTickSegmentIndex = this.dashboardChartMakeTickIndexResolver(labels);
            const tickColorMuted = "#94a3b8";
            const tickColorEmphasis = "#0f172a";
            const tickFontSizeEmphasis = labelFontSize + 1.25;

            return {
                type: "bar",
                plugins: [this.dashboardChartNoSalesWatermarkPlugin(segmentHasSales)],
                data: {
                    labels,
                    datasets: [
                        {
                            label: this.MODULE.texts.chart.datasetLegend,
                            data,
                            backgroundColor: barBgPerIndex,
                            borderColor: barBorderPerIndex,
                            borderWidth: barBorderWPerIndex,
                            borderRadius: {
                                topLeft: 8,
                                topRight: 8,
                                bottomLeft: 0,
                                bottomRight: 0
                            },
                            borderSkipped: false,
                            maxBarThickness,
                            hoverBackgroundColor: barHoverBgPerIndex,
                            hoverBorderColor: barHoverBorderPerIndex
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            bottom: tickRotation > 0 ? 22 : 10,
                            left: 2,
                            right: 2
                        }
                    },
                    animation: {duration: 550},
                    interaction: {mode: "index", intersect: false},
                    datasets: {
                        bar: {categoryPercentage, barPercentage}
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: "bottom",
                            align: "center",
                            labels: {
                                usePointStyle: true,
                                pointStyle: "rect",
                                padding: 18,
                                boxWidth: 12,
                                boxHeight: 8,
                                color: this.config.colors.charts.default.titleColor,
                                font: {size: 12, weight: "600"}
                            }
                        },
                        tooltip: {
                            backgroundColor: this.config.colors.charts.default.backgroundColor,
                            bodyColor: this.config.colors.charts.default.bodyColor,
                            borderColor: this.config.colors.charts.default.borderColor,
                            borderWidth: 1,
                            titleColor: this.config.colors.charts.default.titleColor,
                            displayColors: true,
                            padding: 12,
                            filter: (tooltipItem) => {

                                return Boolean(segmentHasSales[tooltipItem.dataIndex]);

                            },
                            callbacks: {
                                title(items) {

                                    if(!items.length) return "";

                                    const seg = segments[items[0].dataIndex];

                                    if(seg.kind === "single") {

                                        const hour = seg.hour;
                                        return `${vm.dashboardChartFormatHourLabelAmpm(hour)} – ${vm.dashboardChartFormatHourEndAmpm(hour)}`;

                                    }

                                    return `${vm.dashboardChartFormatHourLabelAmpm(seg.startHour)} – ${vm.dashboardChartFormatHourEndAmpm(seg.endHour)}`;

                                },
                                label(ctx) {

                                    const v = ctx.parsed.y;
                                    return ` ${vm.MODULE.texts.chart.tooltipSalesPrefix}${vm.separatorNumber(vm.fixedNumber(v))}`;

                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {display: false},
                            grid: {
                                color: chartGridColor,
                                drawBorder: false,
                                borderColor: chartGridColor
                            },
                            ticks: {
                                autoSkip: false,
                                maxRotation: tickRotation,
                                minRotation: tickRotation,
                                color(ctx) {

                                    const i = resolveTickSegmentIndex(ctx);

                                    if(i < 0 || i >= segmentHasSales.length) return tickColorMuted;

                                    return segmentHasSales[i] ? tickColorEmphasis : tickColorMuted;

                                },
                                font(ctx) {

                                    const i = resolveTickSegmentIndex(ctx);
                                    const has = i >= 0 && i < segmentHasSales.length && segmentHasSales[i];

                                    return {
                                        size: has ? tickFontSizeEmphasis : labelFontSize,
                                        weight: has ? "600" : "400"
                                    };

                                }
                            }
                        },
                        y: {
                            min: 0,
                            max: yAxisMax,
                            title: {
                                display: this.dashboardChartShouldShowYAxisTitle(),
                                text: this.MODULE.texts.chart.yAxisTitle,
                                color: this.config.colors.charts.default.labelColor,
                                font: {size: 11, weight: "600"}
                            },
                            grid: {
                                color: chartGridColor,
                                drawBorder: false,
                                borderColor: chartGridColor
                            },
                            ticks: {
                                color: this.config.colors.charts.default.labelColor,
                                stepSize: yStep,
                                precision: 0,
                                callback(value) {

                                    const n = Utils.roundNumber(value, 0);
                                    return vm.separatorNumber(String(n));

                                }
                            }
                        }
                    }
                }
            };

        },
        initChart() {

            this.dashboardChartSyncChartjsCanvasHeights();

            const records = this.forms.entity.dashboard.data.sales?.records ?? this.forms.entity.dashboard.data.sales?.net?.records ?? [];
            const totalsByHour = this.dashboardChartTotalsByHour(records);
            const sliceHours = this.dashboardChartSliceHours(totalsByHour);
            const segments = this.dashboardChartBuildSegments(sliceHours, totalsByHour);
            const {labels, data, segmentHasSales} = this.dashboardChartLabelsAndData(segments, totalsByHour);
            const maxVal = Math.max(0, ...data);
            const {yStep, yAxisMax} = this.dashboardChartYAxisStepAndMax(maxVal);

            const canvas = document.getElementById("dashboardSalesHourlyChart");
            const primary = this.config.colors.charts.default.primaryColor;
            const barFill = this.hexToRgba(primary, 0.88);
            const barHover = this.hexToRgba(primary, 1);
            const chartGridColor = "#d1d9e3"; // Grid lines slightly more visible than theme --border (#e2e8f0), without excess
            const layout = this.dashboardChartBarLayout(segments.length);

            if(canvas) {

                this.forms.entity.dashboard.data.dashboardChartMinWidthPx = this.dashboardChartScrollMinWidthForSegments(segments);
                this.dashboardChartDestroySalesHourlyIfAny();

                const styles = this.dashboardChartBarIndexStyles(data, primary, barFill, barHover);
                const chartConfig = this.dashboardChartSalesHourlyConfig({
                    labels,
                    data,
                    segments,
                    segmentHasSales,
                    chartGridColor,
                    maxBarThickness: layout.maxBarThickness,
                    categoryPercentage: layout.categoryPercentage,
                    barPercentage: layout.barPercentage,
                    labelFontSize: layout.labelFontSize,
                    tickRotation: layout.tickRotation,
                    yAxisMax,
                    yStep,
                    ...styles
                });

                this.$nextTick(() => {

                    const canvasEl = document.getElementById("dashboardSalesHourlyChart");

                    if(!canvasEl) return;

                    dashboardSalesChartInstance = new Chart(canvasEl, chartConfig);

                });

            }else {

                this.forms.entity.dashboard.data.dashboardChartMinWidthPx = 0;
                this.dashboardChartDestroySalesHourlyIfAny();

            }

        },
        hexToRgba(hex, alpha) {

            const h = hex.replace("#", "");
            const n = h.length === 3 ? h.split("").map(c => c + c).join("") : h;
            const num = parseInt(n, 16);
            const r = (num >> 16) & 255;
            const g = (num >> 8) & 255;
            const b = num & 255;

            return `rgba(${r}, ${g}, ${b}, ${alpha})`;

        },
        // Others
        formatCurrencyTotals: Utils.formatCurrencyTotals,
        fixedNumber(value) {

            return Utils.fixedNumber(value);

        },
        separatorNumber(value, decimals = null) {

            return Utils.separatorNumber(value, decimals);

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        }
    },
    computed: {
        routeActions() {

            return this.config.entity.routes;

        },
        dashboardConsultDateMax: function() {

            return Utils.getCurrentDate();

        },
        dashboardChartNoSales: function() {

            const sales = this.forms.entity.dashboard.data.sales;

            if(sales === null || sales === undefined) return false;

            const records = sales?.records ?? sales?.net?.records;

            return Array.isArray(records) && records.length === 0;

        },
        // Canvas min-width: single hour vs no-sales gap (gap wider; wider still on mobile)
        dashboardChartScrollInnerStyle: function() {

            const w = this.forms.entity.dashboard.data.dashboardChartMinWidthPx;

            if(!w) return {};

            return {
                minWidth: `${w}px`,
                width: "100%"
            };

        },
        dashboardChartHoursRangeCaption: function() {

            const sales = this.forms.entity.dashboard.data.sales?.records ?? this.forms.entity.dashboard.data.sales?.net?.records ?? [];
            const totalsByHour = this.dashboardChartTotalsByHour(sales);

            const hasAnySale = totalsByHour.some((t) => t > 0);

            if(!hasAnySale) return this.MODULE.texts.chart.hoursRangeAllDay;

            const firstHour = totalsByHour.findIndex((t) => t > 0);
            let lastHour = 23;

            for(let i = 23; i >= 0; i--) {

                if(totalsByHour[i] > 0) {

                    lastHour = i;
                    break;

                }

            }

            if(lastHour - firstHour + 1 >= 24) return this.MODULE.texts.chart.hoursRangeAllDay;

            return `${this.dashboardChartFormatHourLabelAmpm(firstHour)} – ${this.dashboardChartFormatHourEndAmpm(lastHour)}`;

        },
        appliedFiltersLabel() {
            if(!this.appliedFilters) return "";

            const {date, branch_id} = this.appliedFilters;
            const branch = this.branchOptions.find(option => option.id === branch_id);
            return [this.legibleFormatDate({dateString: date, type: "date"}), branch?.label].filter(Boolean).join(" · ");
        }
    }
};
</script>

<style scoped>
</style>
