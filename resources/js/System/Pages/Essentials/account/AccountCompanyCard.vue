<template>
    <section class="fg-workspace__panel fg-workspace__company fg-account-page__company-card" aria-labelledby="account-company-title">
        <div class="fg-workspace__company-head">
            <span class="fg-workspace__company-logo">
                <img :src="company.logomark_url" :alt="`Isotipo de ${company.name || 'la empresa'}`">
            </span>
            <div class="fg-workspace__company-identity">
                <h2 id="account-company-title">{{ company.name || 'Empresa' }}</h2>
                <p class="fg-workspace__company-document">
                    <span class="visually-hidden">Tipo de documento:</span>
                    <span class="fg-workspace__company-document-type">{{ company.document_type || 'Sin información' }}</span>
                    <span class="visually-hidden">Número de documento:</span>
                    <span class="fg-copy-value">
                        <span class="fg-workspace__company-document-number">{{ company.document_number || 'Sin información' }}</span>
                        <CopyButton v-if="company.document_number" :value="company.document_number" label="Número de documento de la empresa"/>
                    </span>
                </p>
            </div>
        </div>
    </section>
</template>

<script>
import CopyButton from "@System/Components/CopyButton.vue";

export default {
    name: "AccountCompanyCard",
    components: {CopyButton},
    props: {
        company: {type: Object, required: true}
    }
};
</script>
