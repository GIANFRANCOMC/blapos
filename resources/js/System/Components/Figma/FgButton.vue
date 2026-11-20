<template>
    <button :type="type" :class="['fg-button', tone !== 'primary' && `fg-button--${tone}`]" :disabled="disabled || loading" :aria-busy="loading || undefined">
        <i v-if="loading" class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i>
        <i v-else-if="icon" :class="icon" aria-hidden="true"></i>
        <slot/>
    </button>
</template>
<script setup>
defineProps({type: {type: String, default: 'button'}, tone: {type: String, default: 'primary'}, icon: String, disabled: Boolean, loading: Boolean});
</script>
