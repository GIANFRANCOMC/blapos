<template>
    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading && skeletonEnabled"/>
        <div v-else class="br-page-content">
    <main class="br-guest-section br-guest-attendance">
        <div class="br-guest-container br-guest-attendance__grid">
            <aside class="br-guest-attendance__context">
                <img :src="companyLogo" :alt="companyName" class="br-guest-attendance__logo">
                <p class="br-guest-eyebrow">Asistencia pública</p>
                <h1>Escanea tu carnet</h1>
                <p>Este enlace solo funciona para la sucursal indicada y durante el tiempo autorizado por la empresa.</p>

                <div class="br-guest-attendance__branch">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <div>
                        <strong>{{ access.branch_name || branch.name }}</strong>
                        <span v-if="access.branch_address || branch.address">{{ access.branch_address || branch.address }}</span>
                    </div>
                </div>

                <div class="br-guest-attendance__expires" v-if="access.expires_at">
                    <span>Enlace vigente hasta</span>
                    <strong>{{ expiresAtLabel }}</strong>
                </div>
            </aside>

            <section class="br-guest-card br-guest-attendance__scanner">
                <div class="br-guest-scanner-state" :class="scannerStateClass">
                    <i :class="scannerStateIcon" aria-hidden="true"></i>
                    <div>
                        <strong>{{ scannerStateTitle }}</strong>
                        <span>{{ scannerStateMessage }}</span>
                    </div>
                </div>

                <CodeScanner
                    ref="scannerQr"
                    :showControls="true"
                    :qrbox="270"
                    :fps="20"
                    :limitScan="-1"
                    :canProcess="!processing"
                    @result="onScanCustomer"/>

                <p class="br-guest-help mt-3 mb-0">
                    Si el lector no reconoce el código, ajusta la distancia, mejora la luz o solicita apoyo al personal.
                </p>
            </section>
        </div>
    </main>
        </div>
    </InitialLoadTransition>
</template>

<script>
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "../../Helpers/Alerts.js";
import * as Constants from "../../Helpers/Constants.js";
import * as Requests from "../../Helpers/Requests.js";
import * as Utils from "../../Helpers/Utils.js";

export default {
    mixins: [InitialPageMixin],
    data() {
        return {
            processing: false,
            scannerState: "ready",
            scannerMessage: "Coloca el QR frente a la cámara para registrar tu asistencia.",
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "tracking_attendances"})
                }
            }
        };
    },
    async mounted() {

        await this.loadInitialParams(() => this.initParams());

        this.$nextTick(() => this.$refs.scannerQr?.startScanner?.());
    },
    methods: {
        async initParams() {
            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            return Requests.valid({result: initParams});
        },
        async onScanCustomer(decodedText, decodedResult) {
            if(this.processing) return;

            try {
                Utils.playSound("attendances/scan.mp3");
                const raw = JSON.parse(decodedResult?.result?.text || decodedText || "{}");
                const decodedPayload = Utils.decodeBase64UTF8(raw?.bp || "");
                const payload = JSON.parse(decodedPayload || "{}");
                const customerId = parseInt(payload?.id);

                if(!customerId) {
                    this.showScanProblem("No pudimos leer el carnet. Intenta nuevamente.");
                    return;
                }

                this.processing = true;
                this.scannerState = "processing";
                this.scannerMessage = "Estamos validando la membresía y la sucursal autorizada.";

                const result = await Requests.post({
                    route: this.config.entity.routes.qrCamera,
                    data: {
                        branch_id: this.access.branch_id || this.branch.id,
                        customers: [{customer_id: customerId}]
                    }
                });

                const attendance = result.data?.attendances?.[0] || null;

                if(Requests.valid({result}) && (!attendance || attendance.bool)) {
                    this.scannerState = "success";
                    this.scannerMessage = attendance?.msg || result.data?.msg || "Asistencia registrada correctamente.";
                }else {
                    this.scannerState = "error";
                    this.scannerMessage = attendance?.msg || result.data?.msg || "No se pudo registrar la asistencia.";
                }
            }catch(error) {
                this.showScanProblem("El QR no corresponde a un carnet válido para este registro.");
            }finally {
                setTimeout(() => {
                    this.processing = false;
                    if(this.scannerState !== "ready") {
                        this.scannerState = "ready";
                        this.scannerMessage = "Puedes escanear el siguiente carnet.";
                    }
                }, 2500);
            }
        },
        showScanProblem(message) {
            this.$refs.scannerQr?.decrementScanCounter?.();
            this.scannerState = "warning";
            this.scannerMessage = message;
        }
    },
    computed: {
        company() {
            return this.config.essential.company || {};
        },
        branch() {
            return this.config.essential.branch || {};
        },
        access() {
            return this.config.essential.publicAttendanceAccess || {};
        },
        companyName() {
            return this.company.commercial_name || this.company.legal_name || "Empresa";
        },
        companyLogo() {
            const logo = this.company.logotype || this.company.combinationmark || this.company.logomark;

            return logo
                ? Utils.getAsset(logo, {type: "storage"})
                : Utils.getAsset(this.config.essential.ownerApp?.assets?.img?.logomark, {type: "none", back: 1});
        },
        expiresAtLabel() {
            if(!this.access.expires_at) return "";

            return Utils.legibleFormatDate({
                dateString: new Date(this.access.expires_at * 1000).toISOString().slice(0, 16).replace("T", " "),
                type: "datetime"
            });
        },
        scannerStateClass() {
            return `is-${this.scannerState}`;
        },
        scannerStateIcon() {
            return {
                ready: "fa-solid fa-qrcode",
                processing: "fa-solid fa-spinner fa-spin",
                success: "fa-solid fa-circle-check",
                warning: "fa-solid fa-triangle-exclamation",
                error: "fa-solid fa-circle-xmark"
            }[this.scannerState] || "fa-solid fa-qrcode";
        },
        scannerStateTitle() {
            return {
                ready: "Listo para escanear",
                processing: "Validando asistencia",
                success: "Registro aceptado",
                warning: "Revisa el QR",
                error: "No se registró"
            }[this.scannerState] || "Listo para escanear";
        },
        scannerStateMessage() {
            return this.scannerMessage;
        }
    }
};
</script>
