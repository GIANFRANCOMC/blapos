<template>
    <section class="br-page-skeleton" role="status" aria-live="polite" aria-label="Cargando configuración">
        <span class="visually-hidden">Cargando configuración</span>
        <div class="br-filter-bar br-page-skeleton__filters" aria-hidden="true">
            <div v-for="field in 2" :key="field" class="br-page-skeleton__field">
                <span class="br-page-skeleton__label"></span>
                <span class="br-page-skeleton__control"></span>
            </div>
            <div class="br-filter-bar__actions">
                <span class="br-page-skeleton__button"></span>
                <span class="br-page-skeleton__button"></span>
            </div>
        </div>
        <div class="br-entity-list br-page-skeleton__surface" aria-hidden="true"></div>
    </section>
</template>
