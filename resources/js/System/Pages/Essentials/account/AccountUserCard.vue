<template>
    <section class="br-workspace__panel br-workspace__company br-account-page__user-card" aria-labelledby="account-user-card-title">
        <div class="br-workspace__company-head">
            <span class="br-navbar-user__avatar br-account-page__user-avatar" aria-hidden="true">{{ initials }}</span>
            <div class="br-workspace__company-identity">
                <h2 id="account-user-card-title">{{ account.name || 'Usuario' }}</h2>
                <p class="br-workspace__company-document">
                    <span class="visually-hidden">Documento:</span>
                    <span>{{ documentType }}</span>
                    <span>{{ account.document_number || 'Sin información' }}</span>
                    <CopyButton v-if="account.document_number" :value="account.document_number" label="Número de documento"/>
                </p>
            </div>
        </div>

        <dl class="br-workspace__company-details">
            <div v-for="detail in details" :key="detail.label"
                 :class="['br-workspace__company-detail', detail.full ? 'br-workspace__company-detail--full' : 'br-workspace__company-detail--compact']">
                <span class="br-workspace__company-detail-icon" aria-hidden="true"><i :class="detail.icon"></i></span>
                <div class="br-workspace__company-detail-content">
                    <dt class="colon-at-end">{{ detail.label }}</dt>
                    <dd :class="['br-workspace__company-value', {'is-empty': !detail.value}]">
                        <span class="br-workspace__company-text">{{ detail.value || 'Sin información' }}</span>
                        <CopyButton v-if="detail.copyable && detail.value" :value="detail.value" :label="detail.label"/>
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
        details() {
            return [
                {label: "Nombre completo", value: this.account.name, icon: "fa-regular fa-user", full: true},
                {label: "Tipo de documento", value: this.account.document_type?.toUpperCase(), icon: "fa-regular fa-id-card"},
                {label: "Número de documento", value: this.account.document_number, icon: "fa-regular fa-id-card", copyable: true},
                {label: "Perfil", value: this.account.role, icon: "fa-solid fa-user-shield", full: true}
            ];
        }
    }
};
</script>
