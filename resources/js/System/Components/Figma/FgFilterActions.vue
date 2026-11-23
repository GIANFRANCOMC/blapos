<template>
    <FgButton :type="submit ? 'submit' : 'button'" icon="fa-solid fa-magnifying-glass" :disabled="disabled" :loading="loading" @click="$emit('search')">Buscar</FgButton>
    <FgButton v-if="showClear" tone="link" :disabled="disabled || loading" @click="$emit('clear')">Limpiar</FgButton>
</template>

<script setup>
import FgButton from "./FgButton.vue";

defineEmits(["search", "clear"]);
defineProps({
    disabled: Boolean,
    loading: Boolean,
    submit: Boolean,
    showClear: {type: Boolean, default: true}
});
</script>
