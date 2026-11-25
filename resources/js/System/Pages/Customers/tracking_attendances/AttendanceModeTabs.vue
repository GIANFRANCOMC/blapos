<template>
    <div class="fg-attendance-mode" role="group" aria-label="Modo de asistencia">
        <FgButton v-for="mode in modes" :key="mode.code" tone="secondary"
            :aria-pressed="mode.code === current" @click="mode.code !== current && $emit('select', mode.code)">
            {{ mode.label }}
        </FgButton>
    </div>
</template>

<script setup>
import FgButton from "@System/Components/Figma/FgButton.vue";

defineProps({modes: {type: Array, required: true}, current: {type: String, required: true}});
defineEmits(["select"]);
</script>
