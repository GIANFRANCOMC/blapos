<template>
    <section class="br-workspace__panel br-workspace__company" aria-labelledby="workspace-company-title">
        <div class="br-workspace__company-head">
            <span class="br-workspace__company-logo">
                <img :src="company.logomark_url" :alt="`Isotipo de ${company.commercial_name || company.name}`">
            </span>
            <div class="br-workspace__company-identity">
                <h2 id="workspace-company-title">{{ company.name }}</h2>
                <p class="br-workspace__company-document">
                    <span class="visually-hidden">Tipo de documento:</span>
                    <span class="br-workspace__company-document-type">{{ company.document_type || 'Sin información' }}</span>
                    <span class="visually-hidden">Número de documento:</span>
                    <span class="br-workspace__company-document-number">{{ company.document_number || 'Sin información' }}</span>
                    <CopyButton v-if="company.document_number" :value="company.document_number" label="Número de documento"/>
                </p>
                <p v-if="company.tagline" class="br-workspace__company-tagline">{{ company.tagline }}</p>
            </div>
        </div>

        <dl class="br-workspace__company-details">
            <div
                v-for="detail in details"
                :key="detail.label"
                :class="[
                    'br-workspace__company-detail',
                    detail.full ? 'br-workspace__company-detail--full' : 'br-workspace__company-detail--compact',
                    {'br-workspace__company-detail--divider': detail.divider},
                    detail.tone ? `br-workspace__company-detail--${detail.tone}` : ''
                ]">
                <span class="br-workspace__company-detail-icon" aria-hidden="true"><i :class="detail.icon"></i></span>
                <div class="br-workspace__company-detail-content">
                    <dt class="colon-at-end">{{ detail.label }}</dt>
                    <dd :class="['br-workspace__company-value', {'is-empty': !cleanValue(detail.value)}]">
                        <span class="br-workspace__company-text">{{ cleanValue(detail.value) || 'Sin información' }}</span>
                        <CopyButton v-if="detail.copyable && cleanValue(detail.value)" :value="detail.value" :label="detail.label"/>
                    </dd>
                </div>
            </div>
        </dl>
    </section>
</template>

<script>
import CopyButton from "@System/Components/CopyButton.vue";

export default {
    name: "CompanyCard",
    components: {CopyButton},
    props: {
        company: {type: Object, required: true}
    },
    computed: {
        details() {
            const company = this.company;
            return [
                {label: "Nombre comercial", value: company.commercial_name, icon: "fa-solid fa-building", full: true},
                {label: "Dirección", value: company.address, icon: "fa-solid fa-location-dot", full: true},
                {label: "Correo electrónico", value: company.email, icon: "fa-regular fa-envelope", copyable: true, full: true, divider: true},
                {label: "Teléfono", value: company.telephone, icon: "fa-solid fa-phone", tone: "phone"},
                {label: "WhatsApp", value: company.socials?.whatsapp, icon: "fa-brands fa-whatsapp", tone: "whatsapp"},
                {label: "Facebook", value: company.socials?.facebook, icon: "fa-brands fa-facebook-f", tone: "facebook"},
                {label: "Instagram", value: company.socials?.instagram, icon: "fa-brands fa-instagram", tone: "instagram"}
            ];
        }
    },
    methods: {
        cleanValue(value) {
            return String(value ?? "").trim();
        }
    }
};
</script>
