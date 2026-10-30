<template>
    <section class="br-account-page__panel br-account-skeleton" role="status" aria-live="polite" aria-label="Cargando tu cuenta">
        <span class="visually-hidden">Cargando tu cuenta</span>

        <div class="br-account-page__panel-head" aria-hidden="true">
            <span class="br-account-page__panel-icon"><span class="br-account-skeleton__line br-account-skeleton__panel-symbol"></span></span>
            <span class="br-account-skeleton__heading-copy">
                <span class="br-account-skeleton__line br-account-skeleton__heading-title"></span>
                <span class="br-account-skeleton__line br-account-skeleton__heading-subtitle"></span>
            </span>
        </div>

        <div class="br-account-page__fields" aria-hidden="true">
            <div v-for="index in 5" :key="index" class="br-account-page__field br-account-skeleton__field">
                <span class="br-account-skeleton__line br-account-skeleton__label"></span>
                <span class="br-account-skeleton__control">
                    <span class="br-account-skeleton__line br-account-skeleton__control-icon"></span>
                    <span class="br-account-skeleton__line br-account-skeleton__control-value"></span>
                </span>
            </div>
        </div>

        <footer class="br-account-page__actions" aria-hidden="true">
            <span class="br-account-skeleton__line br-account-skeleton__footer-hint"></span>
            <div class="br-account-skeleton__line br-account-skeleton__footer-button"></div>
        </footer>
    </section>
</template>
