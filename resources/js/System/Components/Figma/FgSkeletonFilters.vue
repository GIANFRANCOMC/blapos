<template>
    <div class="fg-filters fg-catalog-skeleton__filters" aria-hidden="true">
        <div v-for="field in 2" :key="field" class="fg-catalog-skeleton__field">
            <span class="fg-catalog-skeleton__label"><span class="fg-skeleton-line"/></span>
            <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-catalog-skeleton__value"/></span>
        </div>
        <span class="fg-button fg-skeleton-line fg-catalog-skeleton__button">Buscar</span>
        <span class="fg-button fg-button--link fg-skeleton-line fg-catalog-skeleton__clear">Limpiar</span>
    </div>
</template>
