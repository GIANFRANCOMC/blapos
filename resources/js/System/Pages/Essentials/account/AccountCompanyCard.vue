<template>
    <section class="br-workspace__panel br-workspace__company br-account-page__company-card" aria-labelledby="account-company-title">
        <div class="br-workspace__company-head">
            <span class="br-workspace__company-logo">
                <img :src="company.logomark_url" :alt="`Isotipo de ${company.name || 'la empresa'}`">
            </span>
            <div class="br-workspace__company-identity">
                <h2 id="account-company-title">{{ company.name || 'Empresa' }}</h2>
                <p class="br-workspace__company-document">
                    <span class="visually-hidden">Tipo de documento:</span>
                    <span class="br-workspace__company-document-type">{{ company.document_type || 'Sin información' }}</span>
                    <span class="visually-hidden">Número de documento:</span>
                    <span class="br-workspace__company-document-number">{{ company.document_number || 'Sin información' }}</span>
                    <CopyButton v-if="company.document_number" :value="company.document_number" label="Número de documento de la empresa"/>
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
