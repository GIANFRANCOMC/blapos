<template>
    <div class="fg-empty" role="status">
        <i class="fa-regular fa-folder-open fg-empty__icon" aria-hidden="true"></i>
        <strong class="fg-section-title">{{ title }}</strong>
        <p class="fg-help">{{ description }}</p>
        <slot/>
    </div>
</template>
<script setup>
defineProps({title: {type: String, required: true}, description: String});
</script>
