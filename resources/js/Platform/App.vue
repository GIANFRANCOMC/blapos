<template>
    <div class="platform-app">
        <header class="platform-nav">
            <div class="platform-nav__inner">
                <button class="platform-brand platform-reset-button" type="button" @click="openList">
                    <img class="platform-brand__logo" :src="config.brandLogo" alt="Blapos">
                </button>
                <div ref="userMenu" class="platform-user-menu">
                    <button class="platform-user-menu__trigger" type="button" :aria-expanded="showUserMenu" @click="showUserMenu = !showUserMenu">
                        <span>{{ currentUser.name }}</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div v-if="showUserMenu" class="platform-user-menu__dropdown">
                        <div class="platform-user-menu__identity"><strong>{{ currentUser.name }}</strong><small>{{ currentUser.email }}</small></div>
                        <button type="button" @click="openProfile"><i class="fa-regular fa-user"></i><span>Mi perfil</span></button>
                        <button class="is-danger" type="button" :disabled="loggingOut" @click="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Cerrar sesión</span></button>
                    </div>
                </div>
            </div>
        </header>

        <div v-if="notice.message" :class="['platform-notice', `is-${notice.type}`]" role="status">
            <i :class="notice.type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation'"></i>
            <span>{{ notice.message }}</span>
            <button type="button" @click="notice.message = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <main class="platform-main">
            <TenantDetail
                v-if="tenantId"
                :key="tenantId"
                :tenant-id="tenantId"
                :api-base="config.routes.tenantApi"
                @back="openList"
                @notify="showNotice"/>
            <TenantIndex
                v-else
                :api-base="config.routes.tenantApi"
                @open="openTenant"
                @notify="showNotice"/>
        </main>

        <div v-if="showProfile" class="platform-modal" role="dialog" aria-modal="true" aria-labelledby="platformProfileTitle" @mousedown.self="closeProfile">
            <form class="platform-modal__dialog platform-modal__dialog--compact" @submit.prevent="saveProfile">
                <header class="platform-modal__head">
                    <div class="platform-modal__title-wrap">
                        <span class="platform-modal__icon"><i class="fa-regular fa-user"></i></span>
                        <div><span class="platform-eyebrow">Cuenta</span><h2 id="platformProfileTitle">Mi perfil</h2><p>Actualiza tus datos de acceso a la administración.</p></div>
                    </div>
                    <button class="platform-icon-button" type="button" aria-label="Cerrar" :disabled="savingProfile" @click="closeProfile"><i class="fa-solid fa-xmark"></i></button>
                </header>
                <div class="platform-modal__body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Nombre <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="profileForm.name" class="form-control" autocomplete="name" maxlength="150" required></div>
                        <div class="col-12"><label class="form-label">Correo <span class="platform-required" aria-hidden="true">*</span></label><input v-model.trim="profileForm.email" class="form-control" type="email" autocomplete="email" maxlength="190" required></div>
                        <div class="col-12"><label class="form-label">Contraseña actual <span class="platform-required" aria-hidden="true">*</span></label><input v-model="profileForm.current_password" class="form-control" type="password" autocomplete="current-password" maxlength="255" required></div>
                    </div>
                    <div class="platform-modal__section">
                        <strong>Cambiar contraseña <small class="platform-optional">Opcional</small></strong>
                        <div class="row g-3 mt-0">
                            <div class="col-12"><label class="form-label">Nueva contraseña</label><input v-model="profileForm.password" class="form-control" type="password" autocomplete="new-password" minlength="12"><small class="platform-field-help">Mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo.</small></div>
                            <div class="col-12"><label class="form-label">Confirmar nueva contraseña <span v-if="profileForm.password" class="platform-required" aria-hidden="true">*</span></label><input v-model="profileForm.password_confirmation" class="form-control" type="password" autocomplete="new-password" :required="Boolean(profileForm.password)"></div>
                        </div>
                    </div>
                </div>
                <footer class="platform-modal__footer">
                    <div><button class="btn platform-btn-subtle" type="button" :disabled="savingProfile" @click="closeProfile">Cerrar</button><button class="btn platform-btn-primary" :disabled="savingProfile"><span v-if="savingProfile" class="spinner-border spinner-border-sm me-1"></span>{{ savingProfile ? "Guardando…" : "Guardar cambios" }}</button></div>
                </footer>
            </form>
        </div>
    </div>
</template>

<script>
import api, {errorMessage} from "./api";
import TenantDetail from "./pages/TenantDetail.vue";
import TenantIndex from "./pages/TenantIndex.vue";

export default {
    name: "PlatformApp",
    components: {TenantDetail, TenantIndex},
    props: {
        config: {type: Object, required: true}
    },
    data() {
        return {
            tenantId: this.resolveTenantId(),
            currentUser: {...this.config.user},
            showUserMenu: false,
            showProfile: false,
            savingProfile: false,
            profileForm: this.emptyProfileForm(),
            loggingOut: false,
            notice: {type: "success", message: ""},
            noticeTimer: null
        };
    },
    mounted() {
        window.addEventListener("popstate", this.syncLocation);
        document.addEventListener("click", this.closeUserMenuOutside);
    },
    beforeUnmount() {
        window.removeEventListener("popstate", this.syncLocation);
        document.removeEventListener("click", this.closeUserMenuOutside);
        window.clearTimeout(this.noticeTimer);
        document.body.classList.remove("platform-modal-open");
    },
    methods: {
        emptyProfileForm() {
            return {name: this.config.user?.name || "", email: this.config.user?.email || "", current_password: "", password: "", password_confirmation: ""};
        },
        resolveTenantId() {
            const match = window.location.pathname.match(/\/tenants\/([^/]+)\/?$/);
            return match ? decodeURIComponent(match[1]) : (this.config.initialTenantId || null);
        },
        syncLocation() {
            this.tenantId = this.resolveTenantId();
        },
        openList() {
            if(window.location.pathname !== this.config.routes.tenants) {
                window.history.pushState({}, "", this.config.routes.tenants);
            }
            this.tenantId = null;
        },
        openTenant(tenant) {
            const url = `${this.config.routes.tenants}/${tenant.id}`;
            window.history.pushState({}, "", url);
            this.tenantId = tenant.id;
        },
        closeUserMenuOutside(event) {
            if(this.showUserMenu && !this.$refs.userMenu?.contains(event.target)) this.showUserMenu = false;
        },
        openProfile() {
            this.profileForm = {name: this.currentUser.name, email: this.currentUser.email, current_password: "", password: "", password_confirmation: ""};
            this.showUserMenu = false;
            this.showProfile = true;
            document.body.classList.add("platform-modal-open");
        },
        closeProfile() {
            if(this.savingProfile) return;
            this.showProfile = false;
            document.body.classList.remove("platform-modal-open");
        },
        async saveProfile() {
            if(this.savingProfile) return;
            this.savingProfile = true;
            try {
                const {data} = await api.patch(this.config.routes.profile, this.profileForm);
                this.currentUser = {...data.data};
                this.showProfile = false;
                document.body.classList.remove("platform-modal-open");
                this.showNotice(data.message);
            } catch(error) {
                this.showNotice({type: "danger", message: errorMessage(error, "No fue posible actualizar el perfil.")});
            } finally {
                this.savingProfile = false;
            }
        },
        showNotice(payload) {
            window.clearTimeout(this.noticeTimer);
            this.notice = typeof payload === "string"
                ? {type: "success", message: payload}
                : {type: payload.type || "success", message: payload.message};
            this.noticeTimer = window.setTimeout(() => { this.notice.message = ""; }, 6000);
        },
        async logout() {
            if(this.loggingOut || !window.confirm("¿Deseas cerrar la sesión de administración?")) return;

            this.showUserMenu = false;
            this.loggingOut = true;
            try {
                await api.post(this.config.routes.logout);
                window.location.assign(this.config.routes.login);
            } catch(error) {
                this.showNotice({type: "danger", message: errorMessage(error, "No fue posible cerrar la sesión.")});
                this.loggingOut = false;
            }
        }
    }
};
</script>
