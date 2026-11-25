<template>
    <div class="fg-account-page__layout fg-account-skeleton fg-skeleton" role="status" aria-live="polite" aria-label="Cargando tu cuenta">
        <span class="visually-hidden">Cargando tu cuenta</span>

        <section class="fg-account-page__panel" aria-hidden="true">
            <span class="fg-skeleton-line fg-account-skeleton__heading-title"></span>
            <div class="fg-account-page__fields">
                <div v-for="index in 4" :key="index" class="fg-account-page__field fg-account-skeleton__field">
                    <div class="fg-field__label-row"><span class="fg-skeleton-line fg-account-skeleton__label"></span></div>
                    <span class="fg-account-skeleton__control">
                        <span class="fg-skeleton-line fg-account-skeleton__control-value"></span>
                    </span>
                </div>
            </div>

            <div class="fg-account-page__scope">
                <span class="fg-skeleton-line fg-account-skeleton__heading-title"></span>
                <span class="fg-skeleton-line fg-account-skeleton__scope-copy"></span>
            </div>
        </section>

        <aside class="fg-account-page__aside" aria-hidden="true">
            <div class="fg-workspace__panel fg-workspace__company fg-account-page__user-card">
                <div class="fg-workspace__company-head fg-account-page__user-head">
                    <span class="fg-skeleton-line fg-account-skeleton__avatar"></span>
                    <span class="fg-account-skeleton__identity-copy">
                        <span class="fg-skeleton-line fg-account-skeleton__identity-name"></span>
                        <span class="fg-skeleton-line fg-account-skeleton__identity-detail"></span>
                    </span>
                </div>
                <div class="fg-workspace__company-details">
                    <div v-for="index in 3" :key="`detail-${index}`" class="fg-workspace__company-detail fg-workspace__company-detail--full fg-account-skeleton__detail">
                        <span class="fg-skeleton-line fg-account-skeleton__detail-icon"></span>
                        <span class="fg-account-skeleton__identity-copy">
                            <span class="fg-skeleton-line fg-account-skeleton__detail-label"></span>
                            <span class="fg-skeleton-line fg-account-skeleton__detail-value"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="fg-workspace__panel fg-workspace__company fg-account-page__company-card">
                <div class="fg-workspace__company-head">
                    <span class="fg-skeleton-line fg-account-skeleton__company-logo"></span>
                    <span class="fg-account-skeleton__identity-copy">
                        <span class="fg-skeleton-line fg-account-skeleton__company-name"></span>
                        <span class="fg-skeleton-line fg-account-skeleton__company-document"></span>
                    </span>
                </div>
            </div>
        </aside>
    </div>
</template>
