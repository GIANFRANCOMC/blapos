<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading"/>
        <div v-else class="br-page-content">
            <div class="br-dashboard">
                <div class="row mb-4 align-items-start br-dashboard__split">
                    <div class="col-12 col-md-6 col-lg-8">
                        <section class="br-dashboard__kpis br-dashboard__kpis--split row g-2 mb-0" :aria-label="MODULE.texts.chart.kpiSectionAria">
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="br-dashboard-kpi br-dashboard-kpi--success h-100">
                                    <div class="br-dashboard-kpi__inner">
                                        <div class="br-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-sack-dollar"></i>
                                        </div>
                                        <div class="br-dashboard-kpi__body">
                                            <p class="br-dashboard-kpi__label" v-text="MODULE.texts.kpi.salesTotal"></p>
                                            <p class="br-dashboard-kpi__value" v-text="'S/ '+separatorNumber(fixedNumber(forms.entity.dashboard.data.sales?.net?.total ?? 0))"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="br-dashboard-kpi br-dashboard-kpi--danger h-100">
                                    <div class="br-dashboard-kpi__inner">
                                        <div class="br-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-sack-xmark"></i>
                                        </div>
                                        <div class="br-dashboard-kpi__body">
                                            <p class="br-dashboard-kpi__label" v-text="MODULE.texts.kpi.salesCanceled"></p>
                                            <p class="br-dashboard-kpi__value" v-text="'S/ '+separatorNumber(fixedNumber(forms.entity.dashboard.data.sales?.canceled?.total ?? 0))"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="br-dashboard-kpi br-dashboard-kpi--info h-100">
                                    <div class="br-dashboard-kpi__inner">
                                        <div class="br-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-clipboard-user"></i>
                                        </div>
                                        <div class="br-dashboard-kpi__body">
                                            <p class="br-dashboard-kpi__label" v-text="MODULE.texts.kpi.attendancesToday"></p>
                                            <p class="br-dashboard-kpi__value" v-text="separatorNumber(forms.entity.dashboard.data.attendances?.count ?? 0)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="br-dashboard-kpi br-dashboard-kpi--primary h-100">
                                    <div class="br-dashboard-kpi__inner">
                                        <div class="br-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </div>
                                        <div class="br-dashboard-kpi__body">
                                            <p class="br-dashboard-kpi__label" v-text="MODULE.texts.kpi.expiringSubscriptions"></p>
                                            <p class="br-dashboard-kpi__value" v-text="separatorNumber(forms.entity.dashboard.data.expiringSubscriptions?.count ?? 0)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4 col-xl">
                                <div class="br-dashboard-kpi br-dashboard-kpi--warning h-100">
                                    <div class="br-dashboard-kpi__inner">
                                        <div class="br-dashboard-kpi__icon" aria-hidden="true">
                                            <i class="fa-solid fa-building-user"></i>
                                        </div>
                                        <div class="br-dashboard-kpi__body">
                                            <p class="br-dashboard-kpi__label" v-text="MODULE.texts.kpi.branchesActive"></p>
                                            <p class="br-dashboard-kpi__value" v-text="separatorNumber(forms.entity.dashboard.data.branches?.active_count ?? 0)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 br-dashboard__split-date align-self-stretch mt-3 mt-md-2 px-4 px-md-3">
                        <section class="br-dashboard__split-pane h-100 d-flex flex-column" :aria-label="MODULE.texts.date.sectionAria">
                            <div class="br-dashboard-date br-dashboard-date--split br-dashboard-date--split-fill flex-grow-1 d-flex flex-column">
                                <div class="br-dashboard-date__content flex-grow-1 d-flex flex-column">
                                    <div class="br-dashboard-date__main flex-grow-1 d-flex flex-column">
                                        <template v-if="!forms.entity.dashboard.data.dateEditing">
                                            <p class="br-dashboard-date__eyebrow" v-text="MODULE.texts.date.eyebrow"></p>
                                            <p class="br-dashboard-date__value" :title="reportDateLabel" v-text="consultationDateLong"></p>
                                            <div class="br-dashboard-date__actions">
                                                <button type="button" class="br-btn br-btn-sm br-btn-secondary" @click="startDashboardDateEdit">
                                                    <span v-text="MODULE.texts.date.consultButton"></span>
                                                </button>
                                            </div>
                                        </template>
                                        <div v-else
                                            class="br-dashboard-date__editor br-dashboard-date__editor--stack d-flex flex-wrap align-items-end justify-content-end gap-2 pt-1 w-100"
                                            role="group"
                                            :aria-label="MODULE.texts.date.editorAria">
                                            <div class="flex-grow-1 align-self-end" style="min-width: 10rem;">
                                                <InputDate
                                                    v-model="forms.entity.dashboard.data.dateAux"
                                                    hasDiv
                                                    :title="MODULE.texts.date.inputTitle"
                                                    isRequired
                                                    :max="dashboardConsultDateMax"
                                                    :titleClass="['form-label', 'colon-at-end', 'mb-1']"
                                                    :divClass="['mb-0', 'br-dashboard-date__input-wrap']"/>
                                            </div>
                                            <div class="d-flex flex-column gap-1 flex-shrink-0 align-self-end">
                                                <button
                                                    type="button"
                                                    class="br-btn br-btn-xs br-btn-danger"
                                                    :aria-label="MODULE.texts.date.cancelAria"
                                                    @click="cancelDashboardDateEdit">
                                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="br-btn br-btn-xs br-btn-success"
                                                    :aria-label="MODULE.texts.date.applyAria"
                                                    :disabled="!canApplyDashboardDate"
                                                    @click="applyDashboardDate">
                                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="row g-3 mb-4 pt-2" aria-labelledby="br-dashboard-chart-title">
                    <div class="col-12">
                        <div class="br-dashboard-chart-section__title-wrap">
                            <h2 id="br-dashboard-chart-title" class="br-dashboard-chart-section__title" v-text="MODULE.texts.chart.sectionTitle"></h2>
                            <p class="br-dashboard-chart-section__subtitle" v-text="dashboardChartHoursRangeCaption"></p>
                            <p v-if="!dashboardChartNoSales" class="br-dashboard-chart-scroll-hint" v-text="MODULE.texts.chart.scrollHint"></p>
                        </div>
                        <div class="br-dashboard-chart-panel">
                            <div
                                v-if="!dashboardChartNoSales"
                                class="br-dashboard-chart-scroll"
                                role="region"
                                :aria-label="MODULE.texts.chart.chartScrollRegionAria">
                                <div
                                    class="br-dashboard-chart-scroll-inner br-dashboard-chart-wrap br-dashboard-chart-wrap--hourly"
                                    :style="dashboardChartScrollInnerStyle">
                                    <canvas
                                        id="dashboardSalesHourlyChart"
                                        class="chartjs br-dashboard-chart-canvas"
                                        data-height="300"
                                        :aria-label="MODULE.texts.chart.canvasAria"
                                        role="img"></canvas>
                                </div>
                            </div>
                            <div
                                v-else
                                class="br-dashboard-chart-wrap br-dashboard-chart-wrap--hourly br-dashboard-chart-wrap--hourly-empty">
                                <div
                                    class="br-dashboard-chart-empty-overlay"
                                    role="status"
                                    aria-live="polite">
                                    <span class="br-dashboard-chart-empty-overlay__text" v-text="MODULE.texts.chart.noSales"></span>
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
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";

const MODULE_CONFIG = {
    entity: "dashboard",
    menuId: "menu-parent-workspace",
    pageTitle: "Dashboard",
    breadcrumbParent: "Esenciales"
};

const TEXTS = {
    kpi: {
        salesTotal: "Ventas netas",
        salesCanceled: "Ventas anuladas",
        attendancesToday: "Asistencias del día",
        expiringSubscriptions: "Membresías por vencer",
        branchesActive: "Sucursales activas"
    },
    date: {
        eyebrow: "Consulta actual",
        consultButton: "Consultar fecha",
        inputTitle: "Fecha a consultar",
        editorAria: "Editar fecha consultada",
        cancelAria: "Cancelar",
        applyAria: "Aplicar",
        placeholder: "Selecciona una fecha",
        reportPrefix: "Fecha: ",
        sectionAria: "Fecha consultada"
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

let dashboardSalesChartInstance = null;

export default {
    mixins: [InitialPageMixin],
    name: "DashboardMain",
    components: {
        //
    },
    data() {
        return {
            forms: {
                entity: {
                    dashboard: {
                        data: {
                            date: "",
                            dateAux: "",
                            dateEditing: false,
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
            options: {},
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
    mounted: async function() {

        Utils.navbarItem(this.config.entity.page.menu.id, {});

        let initParams = await this.loadInitialParams(() => this.initParams());

        if(initParams) {

            this.initData({});

        }

        this._onDashboardChartResize = () => this.onDashboardChartWindowResize();

        window.addEventListener("resize", this._onDashboardChartResize);

    },
    beforeUnmount() {

        if(this._onDashboardChartResize) window.removeEventListener("resize", this._onDashboardChartResize);

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            return Requests.valid({result: response});

        },
        async initData({loading = false}) {

            let formData = this.forms.entity.dashboard.data;

            if(loading) Alerts.swals({type: "consult"});

            if(!this.isDefined(formData.date)) formData.date = Utils.getCurrentDate();

            let initData = await Requests.get({
                route: this.config.entity.routes.initData,
                data: {page: "main", date: formData.date},
                showAlert: true
            });

            formData.sales = initData.data?.data?.sales;
            formData.attendances = initData.data?.data?.attendances;
            formData.expiringSubscriptions = initData.data?.data?.expiring_subscriptions;
            formData.branches = initData.data?.data?.branches;
            formData.users = initData.data?.data?.users;

            this.$nextTick(() => this.initChart());

            if(loading) Alerts.swals({show: false});

            return Requests.valid({result: initData});

        },
        // Consult date
        startDashboardDateEdit() {

            let formData = this.forms.entity.dashboard.data;
            let max      = Utils.getCurrentDate();
            let dateAux  = formData.date || max;

            if(dateAux > max) dateAux = max;

            formData.dateAux = dateAux;
            formData.dateEditing = true;

        },
        cancelDashboardDateEdit() {

            let formData = this.forms.entity.dashboard.data;

            formData.dateAux = "";
            formData.dateEditing = false;

        },
        applyDashboardDate() {

            let formData = this.forms.entity.dashboard.data;

            if(!this.canApplyDashboardDate) return;

            formData.date = formData.dateAux;
            formData.dateAux = "";
            formData.dateEditing = false;

            this.initData({loading: true});

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

                let sum = 0;

                for(let hh = seg.startHour; hh <= seg.endHour; hh++) {

                    sum += totalsByHour[hh];

                }

                return sum;

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
        isDefined(value) {

            return Utils.isDefined({value});

        },
        fixedNumber(value) {

            return Utils.fixedNumber(value);

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        }
    },
    computed: {
        routeActions() {

            return this.config.entity.routes;

        },
        breadcrumbTitles: function() {

            return [
                this.config.entity.page
            ];

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

            const totalsByHour = Array.from({length: 24}, () => 0);
            const sales = this.forms.entity.dashboard.data.sales?.records ?? this.forms.entity.dashboard.data.sales?.net?.records ?? [];

            sales.forEach((sale) => {

                const saleHour = new Date(sale.created_at).getHours();

                if(saleHour >= 0 && saleHour < 24) totalsByHour[saleHour] = Utils.sum([totalsByHour[saleHour], sale.total]);

            });

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
        canApplyDashboardDate: function() {

            if(!this.forms.entity.dashboard.data.dateEditing) return false;

            const raw = this.forms.entity.dashboard.data.dateAux;

            if(raw === null || raw === undefined) return false;

            const s = String(raw).trim().split("T")[0];

            if(!s) return false;
            if(!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;

            const max = this.dashboardConsultDateMax;

            if(s > max) return false;

            const parts = s.split("-");
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            const dt = new Date(y, m, day);

            return !isNaN(dt.getTime());

        },
        reportDateLabel: function() {

            const d = this.forms.entity.dashboard.data.date;

            if(!d) return "";

            return `${this.MODULE.texts.date.reportPrefix}${this.legibleFormatDate({dateString: d, type: "date"})}`;

        },
        consultationDateLong: function() {

            const d = this.forms.entity.dashboard.data.date;

            if(!d) return this.MODULE.texts.date.placeholder;

            try {

                const raw = String(d).trim();
                const parts = raw.includes("T") ? raw.split("T")[0].split("-") : raw.split("-");

                if(parts.length >= 3) {

                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1;
                    const day = parseInt(parts[2], 10);
                    const date = new Date(y, m, day);

                    if(!isNaN(date.getTime())) {

                        const s = new Intl.DateTimeFormat("es-PE", {
                            weekday: "long",
                            day: "numeric",
                            month: "long",
                            year: "numeric"
                        }).format(date);

                        return s.charAt(0).toUpperCase() + s.slice(1);

                    }

                }

            }catch (e) {

                //

            }

            return this.legibleFormatDate({dateString: d, type: "date"});

        }
    }
};
</script>

<style scoped>
</style>
