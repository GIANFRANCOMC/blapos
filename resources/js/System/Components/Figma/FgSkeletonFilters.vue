<template>
    <div class="fg-filters fg-catalog-skeleton__filters" aria-hidden="true">
        <div v-for="field in 2" :key="field" class="fg-catalog-skeleton__field">
            <span class="fg-catalog-skeleton__label"><span class="fg-skeleton-line"/></span>
            <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-catalog-skeleton__value"/></span>
        </div>
        <FgSkeletonFilterActions/>
    </div>
</template>

<script setup>
import FgSkeletonFilterActions from "./FgSkeletonFilterActions.vue";
</script>
