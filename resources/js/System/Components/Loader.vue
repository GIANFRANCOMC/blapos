<template>
    <div
        v-if="type === 'default'"
        class="fg-loader"
        role="status"
        aria-live="polite">
        <i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i>
        <span class="fg-sr-only" v-text="label"></span>
    </div>
</template>

<script>
export default {
    name: "Loader",
    emits: [],
    props: {
        type: {
            type: String,
            required: false,
            default: "default"
        },
        label: {
            type: String,
            required: false,
            default: "Cargando información"
        }
    }
};
</script>
