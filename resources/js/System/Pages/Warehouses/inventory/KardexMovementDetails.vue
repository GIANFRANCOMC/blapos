<template>
    <div>
        <div class="br-kardex-movement__heading">
            <time v-if="dateLabel" :datetime="movement.created_at">{{ dateLabel }}</time>
            <span :class="['br-inventory-movement', `is-${movement.movement_type}`]">{{ typeLabel }}</span>
        </div>
        <span class="br-inventory__meta">
            <span class="colon-at-end">Responsable</span>
            {{ movement.user?.name || (movement.user_id ? "Usuario no disponible" : "Proceso del sistema") }}
        </span>
        <span v-if="originLabel" class="br-inventory__meta">
            <span class="colon-at-end">Origen</span> {{ originLabel }}
        </span>
        <span v-if="movement.reason" class="br-inventory__meta">
            <span class="colon-at-end">Motivo</span> {{ movement.reason }}
        </span>
        <span v-if="movement.reference" class="br-inventory__meta">
            <span class="colon-at-end">Referencia</span> {{ movement.reference }}
        </span>
    </div>
</template>

<script>
export default {
    name: "KardexMovementDetails",
    props: {
        movement: {type: Object, required: true},
        dateLabel: {type: String, required: true},
        typeLabel: {type: String, default: ""},
        originLabel: {type: String, default: ""}
    }
};
</script>
