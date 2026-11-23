<template>
    <span class="fg-product-codes" v-text="value || '—'"></span>
</template>

<script setup>
defineProps({value: {type: String, default: ""}});
</script>
