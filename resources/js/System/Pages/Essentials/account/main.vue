<template>
    <main class="br-account-page" aria-labelledby="account-title">
        <div class="br-account-page__breadcrumb">
            <Breadcrumb :list="[{title: 'Inicio'}, {title: 'Mi cuenta', active: true}]"/>
        </div>

        <header class="br-account-page__hero">
            <div class="br-account-page__intro">
                <h1 id="account-title">Mi cuenta</h1>
                <p>Actualiza tus datos personales y la información de tu cuenta.</p>
            </div>

            <div v-if="account" class="br-account-page__identity" aria-label="Tu usuario y empresa">
                <div class="br-account-page__identity-person">
                    <span class="br-account-page__avatar" aria-hidden="true">{{ userInitial }}</span>
                    <div class="br-account-page__identity-text">
                        <div class="br-account-page__name-line">
                            <strong :title="account.name">{{ account.name }}</strong>
                            <span v-if="account.role" class="br-account-page__role">{{ account.role }}</span>
                        </div>
                        <small :title="account.email">{{ account.email }}</small>
                    </div>
                </div>
                <div class="br-account-page__identity-company">
                    <span class="br-account-page__company-icon" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
                    <div class="br-account-page__identity-text">
                        <strong :title="company.name">{{ company.name }}</strong>
                        <small>{{ companyDocument }}</small>
                    </div>
                </div>
            </div>
        </header>

        <div v-if="loading" class="br-account-page__loading"><Loader/></div>
        <div v-else-if="loadError" class="br-account-page__load-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ loadError }}</span>
            <button type="button" class="br-btn br-btn-outline-secondary" @click="loadAccount">Reintentar</button>
        </div>

        <template v-else>
            <div v-if="notice" class="br-account-page__notice" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span>{{ notice }}</span>
            </div>

            <form class="br-account-page__panel" novalidate @submit.prevent="saveAccount">
                <div class="br-account-page__panel-head">
                    <span class="br-account-page__panel-icon" aria-hidden="true"><i class="fa-regular fa-user"></i></span>
                    <div>
                        <h2>Datos personales</h2>
                        <p>Información con la que te identificas dentro de la empresa.</p>
                    </div>
                </div>

                <div class="br-account-page__fields">
                    <div class="br-account-page__field">
                        <InputText v-model="form.name" title="Nombre completo" :is-required="true" :maxlength="100" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.name || []">
                            <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-user" aria-hidden="true"></i></span></template>
                        </InputText>
                    </div>
                    <div class="br-account-page__field">
                        <InputBase v-model="form.email" input-type="email" title="Correo electrónico" :is-required="true" :maxlength="100" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.email || []">
                            <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span></template>
                        </InputBase>
                    </div>
                    <div class="br-account-page__field">
                        <InputText v-model="form.phone_number" title="Teléfono" :maxlength="15" placeholder="Sin información" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.phone_number || []">
                            <template #inputGroupPrepend><span class="input-group-text"><i class="fa-solid fa-phone" aria-hidden="true"></i></span></template>
                        </InputText>
                    </div>
                    <div class="br-account-page__field">
                        <InputSelect v-model="form.gender" title="Género" :options="genderOptions" default-option-label="Sin especificar" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.gender || []">
                            <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-user" aria-hidden="true"></i></span></template>
                        </InputSelect>
                    </div>
                    <div class="br-account-page__field">
                        <InputDate v-model="form.birthdate" title="Fecha de nacimiento" :max="today" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.birthdate || []">
                            <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span></template>
                        </InputDate>
                    </div>
                </div>

                <footer class="br-account-page__actions">
                    <span>Los cambios se aplicarán a tu cuenta.</span>
                    <button type="submit" class="br-btn br-btn-primary" :disabled="saving">
                        <span>{{ saving ? 'Guardando...' : 'Guardar cambios' }}</span>
                    </button>
                </footer>
            </form>
        </template>
    </main>
</template>

<script>
import * as Requests from "@System/Helpers/Requests.js";
import * as Alerts from "@System/Helpers/Alerts.js";

const emptyForm = () => ({name: "", email: "", phone_number: "", gender: "", birthdate: ""});

export default {
    data() {
        return {
            account: null,
            company: {name: "", document_type: "", document_number: ""},
            form: emptyForm(),
            errors: {},
            loading: true,
            saving: false,
            loadError: "",
            notice: "",
            genderOptions: [
                {code: "male", label: "Masculino"},
                {code: "female", label: "Femenino"},
                {code: "other", label: "Otro"}
            ]
        };
    },
    computed: {
        userInitial() {
            return Array.from((this.account?.name || "").trim())[0]?.toLocaleUpperCase("es") || "U";
        },
        companyDocument() {
            return [this.company.document_type, this.company.document_number].filter(Boolean).join(" ") || "Sin documento registrado";
        },
        today() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        }
    },
    mounted() {
        this.loadAccount();
    },
    methods: {
        async loadAccount() {
            this.loading = true;
            this.loadError = "";
            const result = await Requests.get({route: window.location.pathname});
            this.loading = false;

            if(!result.bool || !result.data?.account) {
                this.loadError = result.data?.msg || "No fue posible cargar los datos de tu cuenta.";
                return;
            }

            this.account = result.data.account;
            this.company = result.data.company || this.company;
            this.fillForm(this.account);
        },
        fillForm(account) {
            this.form = {
                name: account.name || "",
                email: account.email || "",
                phone_number: account.phone_number || "",
                gender: account.gender || "",
                birthdate: account.birthdate ? String(account.birthdate).slice(0, 10) : ""
            };
        },
        async saveAccount() {
            if(this.saving) return;

            this.saving = true;
            this.errors = {};
            this.notice = "";
            const result = await Requests.patch({route: window.location.pathname, data: this.form});
            this.saving = false;

            if(!result.bool) {
                this.errors = result.errors || {};
                if(result.code !== 422) Alerts.toastrs({type: "error", subtitle: result.data?.msg || "No se pudo guardar la cuenta."});
                return;
            }

            this.account = result.data.account;
            this.fillForm(this.account);
            this.notice = result.data.msg;
            Alerts.toastrs({type: "success", subtitle: result.data.msg});
        }
    }
};
</script>
