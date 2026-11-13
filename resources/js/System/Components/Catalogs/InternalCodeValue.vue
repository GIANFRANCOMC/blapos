<template>
    <span class="d-inline-flex align-items-center gap-1 mw-100">
        <span class="text-break" v-text="value || '—'"></span>
        <CopyButton v-if="value" :value="value" label="Código interno"/>
    </span>
</template>

<script setup>
import CopyButton from "@System/Components/CopyButton.vue";

defineProps({value: {type: String, default: ""}});
</script>
