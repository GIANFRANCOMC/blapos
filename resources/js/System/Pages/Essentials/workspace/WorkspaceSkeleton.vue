<template>
    <div class="br-workspace-skeleton" role="status" aria-live="polite" aria-label="Cargando tu espacio">
        <span class="visually-hidden">Cargando tu espacio</span>

        <div class="br-breadcrumb br-workspace__breadcrumb br-workspace-skeleton__breadcrumb" aria-hidden="true">
            <span class="br-workspace-skeleton__line" style="width: 9rem"></span>
        </div>

        <div class="br-workspace__layout" aria-hidden="true">
            <div class="br-workspace__main">
                <div class="br-workspace__section">
                    <div class="br-workspace__section-heading br-workspace-skeleton__heading">
                        <span class="br-workspace-skeleton__line br-workspace-skeleton__eyebrow"></span>
                        <span class="br-workspace-skeleton__line br-workspace-skeleton__title"></span>
                        <span class="br-workspace-skeleton__line br-workspace-skeleton__subtitle"></span>
                    </div>
                    <div class="br-workspace__links">
                        <div v-for="index in 6" :key="index" class="br-workspace__link br-workspace-skeleton__link">
                            <span class="br-workspace-skeleton__line br-workspace-skeleton__action-icon"></span>
                            <span class="br-workspace-skeleton__action-copy">
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__action-title"></span>
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__action-description"></span>
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__action-description br-workspace-skeleton__action-description--short"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="br-workspace__aside">
                <div class="br-workspace__panel br-workspace__company br-workspace-skeleton__company">
                    <div class="br-workspace__company-head">
                        <span class="br-workspace-skeleton__line br-workspace-skeleton__logo"></span>
                        <span class="br-workspace-skeleton__company-head-copy">
                            <span class="br-workspace-skeleton__line" style="width: 88%; height: 0.85rem"></span>
                            <span class="br-workspace-skeleton__line" style="width: 67%; height: 0.65rem"></span>
                        </span>
                    </div>
                    <div class="br-workspace__company-details">
                        <div v-for="index in 3" :key="`full-${index}`" class="br-workspace__company-detail br-workspace__company-detail--full br-workspace-skeleton__detail">
                            <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-icon"></span>
                            <span class="br-workspace-skeleton__detail-copy">
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-label"></span>
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-value"></span>
                            </span>
                        </div>
                        <div v-for="index in 4" :key="`compact-${index}`" class="br-workspace__company-detail br-workspace__company-detail--compact br-workspace-skeleton__detail br-workspace-skeleton__detail--compact">
                            <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-icon"></span>
                            <span class="br-workspace-skeleton__detail-copy">
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-label"></span>
                                <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-value"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="br-workspace__support br-workspace-skeleton__support" aria-hidden="true">
            <span class="br-workspace__support-icon"><span class="br-workspace-skeleton__line br-workspace-skeleton__support-symbol"></span></span>
            <span class="br-workspace__support-intro br-workspace-skeleton__support-copy">
                <span class="br-workspace-skeleton__line" style="width: 8rem; height: 0.8rem"></span>
                <span class="br-workspace-skeleton__line" style="width: 12rem; max-width: 100%; height: 0.7rem"></span>
            </span>
            <span class="br-workspace__support-channel br-workspace__support-channel--email br-workspace-skeleton__support-copy">
                <span class="br-workspace-skeleton__line" style="width: 85%; height: 0.8rem"></span>
                <span class="br-workspace-skeleton__line" style="width: 55%; height: 0.65rem"></span>
            </span>
            <span class="br-workspace__support-channel br-workspace__support-channel--phone br-workspace-skeleton__support-copy">
                <span class="br-workspace-skeleton__line" style="width: 82%; height: 0.8rem"></span>
                <span class="br-workspace-skeleton__line" style="width: 48%; height: 0.65rem"></span>
            </span>
            <span class="br-workspace__support-cta br-workspace-skeleton__support-button"></span>
        </div>
    </div>
</template>
