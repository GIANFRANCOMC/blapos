<template>
    <section>
        <div class="platform-heading">
            <div>
                <span class="platform-eyebrow">Administración central</span>
                <h1 class="platform-title">Clientes tenant</h1>
                <p class="platform-subtitle">Controla el acceso, estado y configuración de cada organización.</p>
            </div>
            <button class="btn platform-btn-primary platform-action-button text-white" type="button" @click="openCreateModal">
                <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Nuevo cliente</span>
            </button>
        </div>

        <div class="platform-kpi-grid">
            <button v-for="card in countCards" :key="card.code" :class="['platform-card', 'platform-kpi', {'is-selected': filters.status === card.filter}]" type="button" @click="selectStatus(card.filter)">
                <span :class="['platform-kpi__icon', `is-${card.code}`]"><i :class="card.icon"></i></span>
                <span><strong class="platform-kpi__value">{{ card.value }}</strong><small class="platform-kpi__label">{{ card.label }}</small></span>
            </button>
        </div>

        <div class="platform-card">
            <div class="platform-toolbar">
                <div class="platform-search"><i class="fa-solid fa-magnifying-glass"></i><input v-model="filters.search" type="search" placeholder="Buscar por cliente, dominio o base de datos" @input="scheduleSearch"></div>
                <select v-model="filters.status" class="form-select platform-filter" @change="loadTenants(1)">
                    <option value="">Todos los estados</option><option value="active">Activos</option><option value="inactive">Inactivos</option><option value="suspended">Suspendidos</option><option value="maintenance">Mantenimiento</option><option value="provisioning">En preparación</option><option value="provisioning_failed">Preparación fallida</option>
                </select>
                <button class="platform-icon-button" type="button" title="Actualizar" :disabled="loading" @click="loadTenants(meta.current_page)"><i class="fa-solid fa-rotate"></i></button>
            </div>

            <div v-if="loading" class="platform-table-skeleton"><span v-for="index in 5" :key="index"></span></div>
            <div v-else-if="tenants.length" class="table-responsive">
                <table class="table platform-table align-middle mb-0">
                    <thead><tr><th>Cliente</th><th>Dominio</th><th>Base de datos</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
                    <tbody><tr v-for="tenant in tenants" :key="tenant.id">
                        <td><button class="platform-tenant-name" type="button" @click="$emit('open', tenant)"><span>{{ tenant.slug.charAt(0).toUpperCase() }}</span><strong>{{ tenant.slug }}</strong></button></td>
                        <td><a v-if="tenant.url" :href="tenant.url" target="_blank" rel="noopener noreferrer">{{ tenant.domain }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a><span v-else class="text-muted">Sin dominio</span></td>
                        <td><code>{{ tenant.database_name }}</code></td>
                        <td><span :class="['platform-status', `platform-status--${tenant.status}`]">{{ statusLabel(tenant.status) }}</span></td>
                        <td class="text-end"><button class="btn btn-sm platform-manage-button" type="button" :aria-label="`Configurar ${tenant.slug}`" @click="$emit('open', tenant)"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Configurar</span><i class="fa-solid fa-chevron-right platform-manage-button__arrow" aria-hidden="true"></i></button></td>
                    </tr></tbody>
                </table>
            </div>
            <div v-else class="platform-empty"><i class="fa-regular fa-building"></i><strong>No encontramos clientes</strong><span>Ajusta los filtros o crea el primer tenant.</span></div>

            <div v-if="meta.last_page > 1" class="platform-pagination"><span>{{ meta.total }} clientes</span><div><button type="button" :disabled="meta.current_page <= 1" @click="loadTenants(meta.current_page - 1)"><i class="fa-solid fa-chevron-left"></i></button><span>{{ meta.current_page }} / {{ meta.last_page }}</span><button type="button" :disabled="meta.current_page >= meta.last_page" @click="loadTenants(meta.current_page + 1)"><i class="fa-solid fa-chevron-right"></i></button></div></div>
        </div>

        <div v-if="showCreate" class="platform-modal" role="dialog" aria-modal="true" aria-labelledby="createTenantTitle" @mousedown.self="closeCreateModal">
            <form class="platform-modal__dialog" @submit.prevent="createTenant">
                <header class="platform-modal__head">
                    <div class="platform-modal__title-wrap">
                        <span class="platform-modal__icon"><i class="fa-solid fa-building-circle-check"></i></span>
                        <div><span class="platform-eyebrow">Nuevo cliente</span><h2 id="createTenantTitle">Crear organización tenant</h2><p>Aprovisiona una base aislada, la empresa y su administrador.</p></div>
                    </div>
                    <button class="platform-icon-button" type="button" aria-label="Cerrar" :disabled="creating" @click="closeCreateModal"><i class="fa-solid fa-xmark"></i></button>
                </header>
                <div class="platform-modal__body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Subdominio <span class="platform-required" aria-hidden="true">*</span></label><div class="input-group"><input v-model.trim="form.slug" class="form-control" autocomplete="off" pattern="[a-z0-9](?:[a-z0-9-]{0,58}[a-z0-9])?" required maxlength="60" @input="normalizeSlug"><span class="input-group-text">.blapos.test</span></div><small class="platform-field-help">Solo minúsculas, números y guiones.</small></div>
                        <div class="col-md-6"><label class="form-label">Documento <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="form.document_number" class="form-control" inputmode="numeric" autocomplete="off" required maxlength="20"></div>
                        <div class="col-md-6"><label class="form-label">Nombre comercial <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="form.commercial_name" class="form-control" autocomplete="organization" required maxlength="180"></div>
                        <div class="col-md-6"><label class="form-label">Razón social <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="form.legal_name" class="form-control" required maxlength="220"></div>
                    </div>
                    <div class="platform-modal__section">
                        <strong>Administrador inicial</strong>
                        <div class="row g-3 mt-0">
                            <div class="col-md-6"><label class="form-label">Nombre <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="form.admin_name" class="form-control" autocomplete="name" required maxlength="150"></div>
                            <div class="col-md-6"><label class="form-label">Correo <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="form.admin_email" class="form-control" type="email" autocomplete="email" required maxlength="190"></div>
                            <div class="col-md-6"><label class="form-label">Contraseña temporal <span class="platform-required" aria-hidden="true">*</span></label><input v-model="form.admin_password" class="form-control" type="password" autocomplete="new-password" minlength="12" required><small class="platform-field-help">Mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.</small></div>
                            <div class="col-md-6"><label class="form-label">Confirmar contraseña <span class="platform-required" aria-hidden="true">*</span></label><input v-model="form.admin_password_confirmation" class="form-control" type="password" autocomplete="new-password" minlength="12" required></div>
                        </div>
                    </div>
                </div>
                <footer class="platform-modal__footer">
                    <div><button class="btn platform-btn-subtle" type="button" :disabled="creating" @click="closeCreateModal">Cancelar</button><button class="btn platform-btn-primary platform-action-button text-white" :disabled="creating"><span v-if="creating" class="spinner-border spinner-border-sm"></span><i v-else class="fa-solid fa-wand-magic-sparkles"></i><span>{{ creating ? "Creando organización…" : "Crear cliente" }}</span></button></div>
                </footer>
            </form>
            <div v-if="creating" class="platform-provisioning-lock" role="status" aria-live="assertive">
                <span class="platform-provisioning-lock__spinner"></span><strong>Preparando el nuevo cliente</strong><p>No cierres esta ventana. Estamos creando y verificando su entorno aislado.</p>
            </div>
        </div>
    </section>
</template>

<script>
import api, {errorMessage} from "../api";

const emptyForm = () => ({slug: "", commercial_name: "", legal_name: "", document_number: "", admin_name: "", admin_email: "", admin_password: "", admin_password_confirmation: ""});

export default {
    name: "TenantIndex",
    props: {apiBase: {type: String, required: true}},
    emits: ["open", "notify"],
    data() {
        return {tenants: [], counts: {total: 0, active: 0, inactive: 0, suspended: 0, provisioning: 0, provisioning_failed: 0, maintenance: 0}, meta: {current_page: 1, last_page: 1, total: 0}, filters: {search: "", status: ""}, loading: true, creating: false, showCreate: false, form: emptyForm(), searchTimer: null};
    },
    computed: {
        countCards() {
            return [
                {code: "total", label: "Total", value: this.counts.total, filter: "", icon: "fa-solid fa-building"},
                {code: "active", label: "Activos", value: this.counts.active, filter: "active", icon: "fa-solid fa-circle-check"},
                {code: "inactive", label: "Inactivos", value: this.counts.inactive, filter: "inactive", icon: "fa-solid fa-circle-pause"},
                {code: "suspended", label: "Suspendidos", value: this.counts.suspended, filter: "suspended", icon: "fa-solid fa-shield-halved"}
            ];
        }
    },
    mounted() { this.loadTenants(); },
    beforeUnmount() { window.clearTimeout(this.searchTimer); window.removeEventListener("beforeunload", this.preventUnload); document.body.classList.remove("platform-modal-open"); },
    methods: {
        async loadTenants(page = 1) {
            this.loading = true;
            try {
                const {data} = await api.get(this.apiBase, {params: {...this.filters, page, per_page: 20}});
                this.tenants = data.data || []; this.counts = data.counts || this.counts; this.meta = data.meta || this.meta;
            } catch(error) { this.$emit("notify", {type: "danger", message: errorMessage(error, "No fue posible cargar los clientes.")}); }
            finally { this.loading = false; }
        },
        scheduleSearch() { window.clearTimeout(this.searchTimer); this.searchTimer = window.setTimeout(() => this.loadTenants(1), 320); },
        selectStatus(status) { this.filters.status = this.filters.status === status && status !== "" ? "" : status; this.loadTenants(1); },
        statusLabel(status) { return {active: "Activo", inactive: "Inactivo", suspended: "Suspendido", provisioning: "En preparación", provisioning_failed: "Preparación fallida", maintenance: "Mantenimiento"}[status] || status; },
        openCreateModal() { this.showCreate = true; document.body.classList.add("platform-modal-open"); },
        closeCreateModal() { if(this.creating) return; this.showCreate = false; document.body.classList.remove("platform-modal-open"); },
        normalizeSlug(event) { this.form.slug = event.target.value.toLowerCase().replace(/[^a-z0-9-]/g, "").replace(/-{2,}/g, "-"); },
        preventUnload(event) { event.preventDefault(); event.returnValue = ""; },
        async createTenant() {
            if(this.creating) return;
            this.creating = true;
            window.addEventListener("beforeunload", this.preventUnload);
            try {
                const {data} = await api.post(this.apiBase, this.form);
                this.$emit("notify", data.message); this.form = emptyForm(); this.showCreate = false; await this.loadTenants(1);
            } catch(error) { this.$emit("notify", {type: "danger", message: errorMessage(error, "No fue posible crear el cliente.")}); }
            finally { this.creating = false; window.removeEventListener("beforeunload", this.preventUnload); if(!this.showCreate) document.body.classList.remove("platform-modal-open"); }
        }
    }
};
</script>
