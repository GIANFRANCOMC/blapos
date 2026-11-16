<template>
    <span class="br-copy-value">
        <span v-text="value || '—'"></span>
        <CopyButton v-if="value" :value="value" label="Código interno"/>
    </span>
</template>

<script setup>
import CopyButton from "@System/Components/CopyButton.vue";

defineProps({value: {type: String, default: ""}});
</script>
