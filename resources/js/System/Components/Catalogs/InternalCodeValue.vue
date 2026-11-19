<template>
    <span class="br-entity-identifier__value" v-text="value || '—'"></span>
</template>

<script setup>
defineProps({value: {type: String, default: ""}});
</script>
