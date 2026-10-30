<template>
    <Breadcrumb :list="[{title: 'Administración'}, {title: 'Rubro y módulos', active: true}]"/>

    <section class="br-business-profile">
        <header class="br-module-heading">
            <div>
                <h1>Rubro y módulos</h1>
                <p>Parte de una configuración sugerida y ajusta las funciones que estarán disponibles para la empresa.</p>
            </div>
        </header>

        <section class="br-business-profile__industry">
            <div>
                <h2>Configuración base por rubro</h2>
                <p>Aplicar un rubro reemplaza la selección actual por su conjunto recomendado.</p>
            </div>
            <div class="br-business-profile__industry-action">
                <label class="br-business-profile__industry-field">
                    <span class="form-label colon-at-end">Rubro</span>
                    <select v-model="selectedIndustryId" class="form-select" :disabled="loading || saving">
                        <option :value="null">Selecciona un rubro</option>
                        <option v-for="industry in industries" :key="industry.id" :value="industry.id">{{ industry.name }}</option>
                    </select>
                </label>
                <button type="button" class="br-btn br-btn-action-update" :disabled="loading || !selectedIndustryId || saving" @click="applyIndustry">
                    <span>Aplicar rubro</span>
                </button>
            </div>
        </section>

        <section class="br-business-profile__modules">
            <header>
                <div>
                    <h2>Personalización por empresa</h2>
                    <p>{{ enabledIds.length }} de {{ modules.length }} funciones activas.</p>
                </div>
                <div class="br-business-profile__bulk">
                    <button type="button" class="br-btn br-btn-sm br-btn-outline-secondary" :disabled="loading || saving" @click="selectAll">Activar todas</button>
                    <button type="button" class="br-btn br-btn-sm br-btn-outline-secondary" :disabled="loading || saving" @click="clearOptional">Limpiar opcionales</button>
                </div>
            </header>

            <div v-if="loading" class="br-loading-state"><Loader/></div>
            <WithoutData v-else-if="modules.length === 0" type="image" text="Sin módulos disponibles" description="No hay funciones configuradas para esta empresa."/>
            <div v-else class="br-business-profile__groups">
                <article v-for="group in moduleGroups" :key="group.id" class="br-business-profile__group">
                    <div class="br-business-profile__group-head">
                        <strong>{{ group.label }}</strong>
                        <span>{{ selectedInGroup(group) }}/{{ group.modules.length }}</span>
                    </div>
                    <label v-for="module in group.modules" :key="module.id" class="br-business-profile__module">
                        <span>
                            <strong>{{ module.dom_label }}</strong>
                            <small>{{ module.description || "Función del sistema" }}</small>
                        </span>
                        <input v-model="enabledIds" type="checkbox" class="form-check-input" :value="module.id" :disabled="isProtected(module)">
                    </label>
                </article>
            </div>

            <footer>
                <span>Mi espacio y Mi cuenta permanecen activos.</span>
                <button type="button" class="br-btn br-btn-primary" :disabled="loading || modules.length === 0 || saving" @click="saveModules">
                    <span>{{ saving ? 'Guardando...' : 'Guardar módulos' }}</span>
                </button>
            </footer>
        </section>
    </section>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

const PROTECTED_ROUTES = ["workspace.index", "account.index"];

export default {
    data() {
        return {
            routes: Requests.config({entity: "business_profile"}).routes,
            industries: [],
            modules: [],
            enabledIds: [],
            selectedIndustryId: null,
            loading: true,
            saving: false
        };
    },
    computed: {
        moduleGroups() {
            const groups = new Map();
            this.modules.forEach(module => {
                const id = module.section_id;
                if(!groups.has(id)) groups.set(id, {id, label: module.section?.dom_label || "Otros", modules: []});
                groups.get(id).modules.push(module);
            });
            return Array.from(groups.values());
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-configuration", {addClass: "open"});
        Utils.navbarItem("menu-configuration-business-profile", {addClass: "active"});
        this.load();
    },
    methods: {
        async load() {
            this.loading = true;
            const result = await Requests.get({route: this.routes.initParams});
            this.loading = false;
            if(!result.bool) return;
            this.industries = result.data.industries || [];
            this.modules = result.data.modules || [];
            this.enabledIds = (result.data.enabled_module_ids || []).map(Number);
        },
        async applyIndustry() {
            const industry = this.industries.find(item => Number(item.id) === Number(this.selectedIndustryId));
            if(!industry) return;
            const confirmation = await Swal.fire({
                title: "Aplicar rubro",
                text: `La selección actual se reemplazará por los módulos recomendados para ${industry.name}.`,
                icon: "question", showCancelButton: true, showCloseButton: true, closeButtonAriaLabel: "Cerrar confirmación", confirmButtonText: "Aplicar rubro", cancelButtonText: "Cancelar"
            });
            if(!confirmation.isConfirmed) return;
            this.saving = true;
            const result = await Requests.post({route: `${this.routes.consult}/apply`, data: {business_industry_id: industry.id}});
            this.saving = false;
            Alerts.toastrs({type: result.bool ? "success" : "error", subtitle: result.data.msg});
            if(result.bool) await this.load();
        },
        async saveModules() {
            this.saving = true;
            const result = await Requests.patch({
                route: `${this.routes.consult}/modules`,
                data: {enabled_module_ids: this.enabledIds}
            });
            this.saving = false;
            Alerts.toastrs({type: result.bool ? "success" : "error", subtitle: result.data.msg});
            if(result.bool) await this.load();
        },
        selectAll() {
            this.enabledIds = this.modules.map(module => Number(module.id));
        },
        clearOptional() {
            this.enabledIds = this.modules.filter(this.isProtected).map(module => Number(module.id));
        },
        isProtected(module) {
            return PROTECTED_ROUTES.includes(module.dom_route);
        },
        selectedInGroup(group) {
            const selected = new Set(this.enabledIds.map(Number));
            return group.modules.filter(module => selected.has(Number(module.id))).length;
        }
    }
};
</script>
