<template>
    <section class="fg-workspace__panel fg-workspace__company fg-account-page__user-card" aria-labelledby="account-user-card-title">
        <div class="fg-workspace__company-head fg-account-page__user-head">
            <span class="fg-navbar-user__avatar fg-account-page__user-avatar" aria-hidden="true">{{ initials }}</span>
            <div class="fg-workspace__company-identity">
                <h2 id="account-user-card-title">{{ account.name || 'Usuario' }}</h2>
                <p class="fg-workspace__company-document">
                    <span class="visually-hidden">Documento:</span>
                    <span>{{ documentType }}</span>
                    <span class="fg-copy-value">
                        <span>{{ account.document_number || 'Sin información' }}</span>
                        <CopyButton v-if="account.document_number" :value="account.document_number" label="Número de documento"/>
                    </span>
                </p>
            </div>
        </div>

        <dl class="fg-workspace__company-details">
            <div v-for="detail in details" :key="detail.label"
                 :class="['fg-workspace__company-detail', detail.full ? 'fg-workspace__company-detail--full' : 'fg-workspace__company-detail--compact']">
                <span class="fg-workspace__company-detail-icon" aria-hidden="true"><i :class="detail.icon"></i></span>
                <div class="fg-workspace__company-detail-content">
                    <dt class="colon-at-end">{{ detail.label }}</dt>
                    <dd :class="['fg-workspace__company-value', {'is-empty': !detail.value}]">
                        <span class="fg-workspace__company-text">{{ detail.value || 'Sin información' }}</span>
                    </dd>
                </div>
            </div>
        </dl>
    </section>
</template>

<script>
import CopyButton from "@System/Components/CopyButton.vue";

export default {
    name: "AccountUserCard",
    components: {CopyButton},
    props: {
        account: {type: Object, required: true}
    },
    computed: {
        initials() {
            return (this.account.name || "").trim().split(/\s+/u).slice(0, 2)
                .map(part => Array.from(part)[0]?.toLocaleUpperCase("es") || "")
                .join("") || "U";
        },
        documentType() {
            return this.account.document_type?.toUpperCase() || "Sin información";
        },
        identification() {
            return [this.account.document_type?.toUpperCase(), this.account.document_number]
                .filter(Boolean).join(" ");
        },
        details() {
            return [
                {label: "Nombre completo", value: this.account.name, icon: "fa-regular fa-user", full: true},
                {label: "Identificación", value: this.identification, icon: "fa-regular fa-id-card", full: true},
                {label: "Perfil", value: this.account.role, icon: "fa-solid fa-user-shield", full: true}
            ];
        }
    }
};
</script>
