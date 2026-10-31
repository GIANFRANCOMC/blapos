<template>
    <div class="br-account-page__layout br-account-skeleton" role="status" aria-live="polite" aria-label="Cargando tu cuenta">
        <span class="visually-hidden">Cargando tu cuenta</span>

        <div class="br-account-page__main">
            <header class="br-account-page__hero">
                <div class="br-account-page__intro">
                    <h1 id="account-title">Mi cuenta</h1>
                    <p>Actualiza tus datos personales y la información de tu cuenta.</p>
                </div>
            </header>

            <section class="br-account-page__panel" aria-hidden="true">
                <div class="br-account-page__panel-head">
                    <span class="br-account-skeleton__heading-copy">
                        <span class="br-account-skeleton__line br-account-skeleton__heading-title"></span>
                        <span class="br-account-skeleton__line br-account-skeleton__heading-subtitle"></span>
                    </span>
                </div>

                <div class="br-account-page__fields">
                    <div v-for="index in 4" :key="index" class="br-account-page__field br-account-skeleton__field">
                        <span class="br-account-skeleton__line br-account-skeleton__label"></span>
                        <span class="br-account-skeleton__control">
                            <span class="br-account-skeleton__line br-account-skeleton__control-icon"></span>
                            <span class="br-account-skeleton__line br-account-skeleton__control-value"></span>
                        </span>
                    </div>
                </div>

                <footer class="br-account-page__actions">
                    <div class="br-account-skeleton__line br-account-skeleton__footer-button"></div>
                </footer>
            </section>
        </div>

        <aside class="br-account-page__aside" aria-hidden="true">
            <CompanyCardSkeleton/>
            <div class="br-workspace__panel br-workspace__company br-account-page__user-card">
                <div class="br-workspace__company-head">
                    <span class="br-account-skeleton__line br-account-skeleton__avatar"></span>
                    <span class="br-account-skeleton__identity-copy">
                        <span class="br-account-skeleton__line br-account-skeleton__identity-name"></span>
                        <span class="br-account-skeleton__line br-account-skeleton__identity-detail"></span>
                    </span>
                </div>
                <div class="br-workspace__company-details">
                    <div v-for="index in 4" :key="`detail-${index}`" class="br-workspace__company-detail br-account-skeleton__detail">
                        <span class="br-account-skeleton__line br-account-skeleton__detail-icon"></span>
                        <span class="br-account-skeleton__identity-copy">
                            <span class="br-account-skeleton__line br-account-skeleton__detail-label"></span>
                            <span class="br-account-skeleton__line br-account-skeleton__detail-value"></span>
                        </span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>

<script>
import CompanyCardSkeleton from "../workspace/CompanyCardSkeleton.vue";

export default {
    components: {CompanyCardSkeleton}
};
</script>
