<template>
    <span class="fg-select-empty" role="status">
        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
        <span v-text="message"></span>
    </span>
</template>

<script>
export default {
    name: "SelectNoOptions",
    props: {
        message: {
            type: String,
            default: "No hay opciones disponibles."
        }
    }
};
</script>
