<template>
    <div class="fg-workspace-skeleton fg-skeleton" role="status" aria-live="polite" aria-label="Cargando tu espacio">
        <span class="visually-hidden">Cargando tu espacio</span>

        <div class="fg-breadcrumb fg-workspace__breadcrumb fg-workspace-skeleton__breadcrumb" aria-hidden="true">
            <span class="fg-skeleton-line" style="width: 9rem"></span>
        </div>

        <div class="fg-workspace__layout" aria-hidden="true">
            <div class="fg-workspace__main">
                <div class="fg-workspace__section">
                    <div class="fg-workspace__section-heading fg-workspace-skeleton__heading">
                        <span class="fg-skeleton-line fg-workspace-skeleton__title"></span>
                        <span class="fg-skeleton-line fg-workspace-skeleton__subtitle"></span>
                    </div>
                    <div class="fg-workspace__links">
                        <div v-for="index in 6" :key="index" class="fg-workspace__link fg-workspace-skeleton__link">
                            <span class="fg-skeleton-line fg-workspace-skeleton__action-icon"></span>
                            <span class="fg-workspace-skeleton__action-copy">
                                <span class="fg-skeleton-line fg-workspace-skeleton__action-title"></span>
                                <span class="fg-skeleton-line fg-workspace-skeleton__action-description"></span>
                                <span class="fg-skeleton-line fg-workspace-skeleton__action-description fg-workspace-skeleton__action-description--short"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="fg-workspace__aside">
                <CompanyCardSkeleton/>
            </aside>
        </div>

        <div class="fg-workspace__support fg-workspace-skeleton__support" aria-hidden="true">
            <span class="fg-workspace__support-icon"><span class="fg-skeleton-line fg-workspace-skeleton__support-symbol"></span></span>
            <span class="fg-workspace__support-intro fg-workspace-skeleton__support-copy">
                <span class="fg-skeleton-line" style="width: 8rem; height: 0.8rem"></span>
                <span class="fg-skeleton-line" style="width: 12rem; max-width: 100%; height: 0.7rem"></span>
            </span>
            <span class="fg-workspace__support-channel fg-workspace__support-channel--email fg-workspace-skeleton__support-copy">
                <span class="fg-skeleton-line" style="width: 85%; height: 0.8rem"></span>
                <span class="fg-skeleton-line" style="width: 55%; height: 0.65rem"></span>
            </span>
            <span class="fg-workspace__support-channel fg-workspace__support-channel--phone fg-workspace-skeleton__support-copy">
                <span class="fg-skeleton-line" style="width: 82%; height: 0.8rem"></span>
                <span class="fg-skeleton-line" style="width: 48%; height: 0.65rem"></span>
            </span>
            <span class="fg-workspace__support-cta fg-workspace-skeleton__support-button"></span>
        </div>
    </div>
</template>

<script>
import CompanyCardSkeleton from "./CompanyCardSkeleton.vue";

export default {
    components: {CompanyCardSkeleton}
};
</script>
