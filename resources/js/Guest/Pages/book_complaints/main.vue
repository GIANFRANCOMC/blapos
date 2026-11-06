<template>
    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading && skeletonEnabled"/>
        <div v-else class="br-page-content">
    <main class="br-guest-section">
        <div class="br-guest-container">
            <header class="br-guest-public-header">
                <p class="br-guest-eyebrow">Canal público</p>
                <h1>Libro de reclamaciones</h1>
                <p>Registra una queja, reclamo o sugerencia. También puedes consultar el estado usando tu código de seguimiento.</p>
            </header>

            <div class="br-guest-switch">
                <button type="button" :class="{'is-active': mode === 'form'}" @click="mode = 'form'">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    Registrar solicitud
                </button>
                <button type="button" :class="{'is-active': mode === 'status'}" @click="mode = 'status'">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    Consultar código
                </button>
            </div>

            <section v-if="mode === 'form'" class="br-guest-card">
                <div class="br-guest-form-grid">
                    <div class="br-guest-field br-guest-field--full">
                        <label>Tipo <span>*</span></label>
                        <div class="br-guest-type-grid">
                            <button
                                v-for="type in types"
                                :key="type.code"
                                type="button"
                                :class="['br-guest-type', {'is-active': form.type === type.code}]"
                                @click="form.type = type.code">
                                <i :class="type.data?.icon" aria-hidden="true"></i>
                                <strong>{{ type.label }}</strong>
                                <small>{{ type.data?.description }}</small>
                            </button>
                        </div>
                        <small class="br-guest-error">{{ firstError("type") }}</small>
                    </div>

                    <div class="br-guest-field">
                        <label>Tipo de documento <span>*</span></label>
                        <v-select v-model="form.identity_document_type" :options="identityDocumentTypes" :clearable="false" :searchable="false"/>
                        <small class="br-guest-error">{{ firstError("identity_document_type_id") || firstError("identity_document_type") }}</small>
                    </div>
                    <div class="br-guest-field">
                        <label>Sucursal <span>*</span></label>
                        <v-select v-model="form.branch" :options="branches" :clearable="false" :searchable="branches.length > 6"/>
                        <small class="br-guest-error">{{ firstError("branch_id") || firstError("branch") }}</small>
                    </div>
                    <div class="br-guest-field">
                        <label>Número de documento <span>*</span></label>
                        <input v-model.trim="form.document_number" class="form-control" type="text" maxlength="30" placeholder="Ej. 12345678">
                        <small class="br-guest-error">{{ firstError("document_number") }}</small>
                    </div>
                    <div class="br-guest-field">
                        <label>Nombre completo <span>*</span></label>
                        <input v-model.trim="form.name" class="form-control" type="text" maxlength="255" placeholder="Nombre y apellido">
                        <small class="br-guest-error">{{ firstError("name") }}</small>
                    </div>
                    <div class="br-guest-field">
                        <label>Correo electrónico</label>
                        <input v-model.trim="form.email" class="form-control" type="email" maxlength="255" placeholder="correo@ejemplo.com">
                        <small class="br-guest-error">{{ firstError("email") }}</small>
                    </div>
                    <div class="br-guest-field">
                        <label>Celular</label>
                        <input v-model.trim="form.phone_number" class="form-control" type="text" maxlength="30" placeholder="987654321">
                        <small class="br-guest-error">{{ firstError("phone_number") }}</small>
                    </div>
                    <div class="br-guest-field br-guest-field--full">
                        <label>Descripción <span>*</span></label>
                        <textarea v-model.trim="form.description" class="form-control" rows="4" maxlength="5000" placeholder="Cuéntanos qué ocurrió, cuándo ocurrió y qué necesitas que revisemos."></textarea>
                        <small class="br-guest-error">{{ firstError("description") }}</small>
                    </div>
                    <div class="br-guest-field br-guest-field--full">
                        <label>Pedido o solución esperada</label>
                        <textarea v-model.trim="form.request" class="form-control" rows="3" maxlength="5000" placeholder="Ej. Solicito revisión, cambio, respuesta o devolución."></textarea>
                        <small class="br-guest-error">{{ firstError("request") }}</small>
                    </div>
                    <div class="br-guest-field br-guest-field--full">
                        <label>Adjuntos</label>
                        <input ref="attachmentsInput" class="form-control" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" @change="setAttachments">
                        <small class="br-guest-help">Puedes adjuntar hasta 5 archivos PDF, JPG o PNG de máximo 5 MB cada uno.</small>
                        <div class="br-guest-files" v-if="attachments.length">
                            <span v-for="file in attachments" :key="file.name">{{ file.name }}</span>
                        </div>
                        <small class="br-guest-error">{{ firstError("attachments") || firstError("attachments.0") }}</small>
                    </div>

                    <div class="br-guest-field br-guest-field--full d-none">
                        <label>Sitio web</label>
                        <input v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div v-if="captchaSiteKey" class="br-guest-field br-guest-field--full">
                        <div ref="turnstile" class="cf-turnstile" :data-sitekey="captchaSiteKey" data-size="flexible"></div>
                        <small class="br-guest-error">{{ firstError("captcha") || firstError("cf-turnstile-response") }}</small>
                    </div>
                </div>

                <footer class="br-guest-card__footer">
                    <span>Usaremos estos datos solo para atender esta solicitud.</span>
                    <button type="button" class="br-guest-btn br-guest-btn-primary" :disabled="saving" @click="submit">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        <span>{{ saving ? "Enviando" : "Enviar solicitud" }}</span>
                    </button>
                </footer>
            </section>

            <section v-if="mode === 'status'" class="br-guest-card">
                <div class="br-guest-status-search">
                    <div class="br-guest-field">
                        <label>Código de seguimiento</label>
                        <input v-model.trim="trackingCode" class="form-control" type="text" maxlength="20" placeholder="Ej. ABC123XYZ789" @keyup.enter="consultStatus">
                    </div>
                    <button type="button" class="br-guest-btn br-guest-btn-secondary" :disabled="consulting || !normalizedTrackingCode" @click="consultStatus">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span>{{ consulting ? "Consultando" : "Consultar" }}</span>
                    </button>
                </div>

                <div class="br-guest-result" v-if="statusResult">
                    <span class="br-guest-result__badge">{{ statusResult.status }}</span>
                    <strong>{{ statusResult.type }} {{ statusResult.tracking_code }}</strong>
                    <p v-if="statusResult.public_response">{{ statusResult.public_response }}</p>
                    <p v-else>Aún no hay una respuesta pública registrada. Conserva tu código para volver a consultar.</p>
                    <small>Registrado: {{ formatDate(statusResult.created_at) }}</small>
                </div>
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

const emptyBookComplaintForm = () => ({
    branch: null,
    identity_document_type: null,
    document_number: "",
    name: "",
    email: "",
    phone_number: "",
    type: null,
    description: "",
    request: "",
    evidence: "",
    website: ""
});

export default {
    mixins: [InitialPageMixin],
    data() {
        return {
            mode: "form",
            saving: false,
            consulting: false,
            trackingCode: "",
            statusResult: null,
            attachments: [],
            form: emptyBookComplaintForm(),
            errors: {},
            options: {},
            captchaWidgetId: null,
            captchaRenderAttempts: 0,
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "book_complaints"})
                }
            }
        };
    },
    async mounted() {

        await this.loadInitialParams(() => this.initParams());
        this.renderCaptcha();
    },
    methods: {
        emptyForm() {
            return emptyBookComplaintForm();
        },
        async initParams() {
            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            this.options.bookComplaints = initParams.data?.config?.bookComplaints || {};
            this.options.identityDocumentTypes = initParams.data?.config?.identityDocumentTypes || {records: []};
            this.options.branches = initParams.data?.config?.branches || {records: []};
            this.form.identity_document_type = this.identityDocumentTypes[0] || null;
            this.form.branch = this.branches[0] || null;
        },
        renderCaptcha() {
            this.$nextTick(() => {
                if(!this.captchaSiteKey || !this.$refs.turnstile) return;
                if(this.$refs.turnstile.dataset.rendered) return;

                if(!window.turnstile) {
                    if(this.captchaRenderAttempts < 10) {
                        this.captchaRenderAttempts++;
                        setTimeout(() => this.renderCaptcha(), 300);
                    }
                    return;
                }

                this.captchaWidgetId = window.turnstile.render(this.$refs.turnstile, {
                    sitekey: this.captchaSiteKey
                });
                this.$refs.turnstile.dataset.rendered = "true";
            });
        },
        setAttachments(event) {
            this.attachments = Array.from(event.target.files || []).slice(0, 5);
        },
        async submit() {
            this.errors = {};
            const formData = new FormData();
            const payload = {
                ...this.form,
                branch_id: this.form.branch?.code || "",
                identity_document_type_id: this.form.identity_document_type?.code || ""
            };

            delete payload.branch;
            delete payload.identity_document_type;

            Object.keys(payload).forEach(key => {
                if(key === "website" && !payload[key]) return;
                formData.append(key, payload[key] ?? "");
            });
            this.attachments.forEach(file => formData.append("attachments[]", file));

            const captchaResponse = this.$refs.turnstile?.querySelector(`input[name="cf-turnstile-response"]`)?.value
                || document.querySelector(`input[name="cf-turnstile-response"]`)?.value
                || "";
            if(this.captchaSiteKey) formData.append("cf-turnstile-response", captchaResponse);

            this.saving = true;
            Alerts.swals({type: "saveForm"});
            const result = await Requests.post({
                route: this.config.entity.routes.store,
                formData
            });
            this.saving = false;

            if(Requests.valid({result})) {
                Alerts.swals({show: false});
                Alerts.generateAlert({
                    type: "success",
                    headerTitle: "Solicitud registrada",
                    msgContent: `<p class="mb-2">Guarda este código para consultar el estado:</p><strong class="fs-4">${result.data.tracking_code}</strong>`
                });
                this.trackingCode = result.data.tracking_code;
                this.form = this.emptyForm();
                this.form.identity_document_type = this.identityDocumentTypes[0] || null;
                this.form.branch = this.branches[0] || null;
                this.attachments = [];
                this.clearAttachmentInput();
                this.resetCaptcha();
            }else {
                Alerts.swals({show: false});
                this.errors = result.errors || {};
                Alerts.generateAlert({
                    type: "error",
                    msgContent: result.data?.msg || "Revisa los datos ingresados."
                });
            }
        },
        async consultStatus() {
            if(!this.normalizedTrackingCode) return;

            this.statusResult = null;
            this.consulting = true;
            const result = await Requests.get({
                route: `${this.config.entity.routes.status}/${encodeURIComponent(this.normalizedTrackingCode)}`,
                showAlert: false
            });
            this.consulting = false;

            if(Requests.valid({result})) {
                this.statusResult = result.data.data;
            }else {
                Alerts.generateAlert({
                    type: "warning",
                    msgContent: result.data?.msg || "No encontramos una solicitud con ese código."
                });
            }
        },
        resetCaptcha() {
            if(!window.turnstile) return;

            if(this.captchaWidgetId !== null) {
                window.turnstile.reset(this.captchaWidgetId);
                return;
            }

            window.turnstile.reset();
        },
        clearAttachmentInput() {
            if(this.$refs.attachmentsInput) this.$refs.attachmentsInput.value = "";
        },
        firstError(key) {
            return this.errors?.[key]?.[0] || "";
        },
        formatDate(value) {
            return Utils.legibleFormatDate({dateString: value, type: "datetime"});
        }
    },
    computed: {
        captchaSiteKey() {
            return this.config.essential.captchaSiteKey;
        },
        normalizedTrackingCode() {
            return this.trackingCode.trim().toUpperCase();
        },
        identityDocumentTypes() {
            return (this.options.identityDocumentTypes?.records || []).map(record => ({
                code: record.id,
                label: record.name
            }));
        },
        branches() {
            return (this.options.branches?.records || []).map(record => ({
                code: record.id,
                label: record.address ? `${record.name} - ${record.address}` : record.name
            }));
        },
        types() {
            return (this.options.bookComplaints?.types || []).map(record => ({
                code: record.code,
                label: record.label,
                data: record
            }));
        }
    },
    watch: {
        mode(value) {
            if(value === "form") this.renderCaptcha();
        }
    }
};
</script>
