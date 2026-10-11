<template>
    <div class="dropdown br-table-actions-dropdown">
        <button
            type="button"
            class="br-btn br-btn-actions br-btn-actions--icon"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            :aria-label="label"
            :disabled="disabled">
            <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end br-btn-actions-menu">
            <slot></slot>
        </ul>
    </div>
</template>

<script>
export default {
    name: "TableActionsDropdown",
    props: {
        label: {
            type: String,
            default: "Acciones del registro"
        },
        disabled: {
            type: Boolean,
            default: false
        }
    }
};
</script>
