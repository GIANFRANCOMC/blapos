<template>
    <div class="fg-loader" role="status" aria-live="polite">
        <img class="fg-loader__icon fa-spin" :src="loadingIcon" width="32" height="32" alt="" aria-hidden="true">
        <span>{{ label }}</span>
    </div>
</template>
<script setup>
import loadingIcon from '../../../../assets/fg/feedback/loading.svg';
defineProps({label: {type: String, default: 'Cargando registros…'}});
</script>
