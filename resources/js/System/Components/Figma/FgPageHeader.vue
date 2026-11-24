<template>
    <header class="fg-page-header">
        <div>
            <h1 class="fg-title">{{ title }}</h1>
            <p v-if="description" class="fg-help">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="fg-page-header__actions"><slot name="actions"/></div>
    </header>
</template>

<script setup>
defineProps({
    title: {type: String, required: true},
    description: String
});
</script>
