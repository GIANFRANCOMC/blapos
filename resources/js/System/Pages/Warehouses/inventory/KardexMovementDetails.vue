<template>
    <div>
        <div class="br-kardex-movement__heading">
            <time v-if="dateLabel" :datetime="movement.created_at">{{ dateLabel }}</time>
            <span :class="['br-inventory-movement', `is-${movement.movement_type}`]">{{ typeLabel }}</span>
        </div>
        <span class="br-inventory__meta">
            <template v-if="originLabel">
                <span class="visually-hidden">Origen:</span>
                {{ originLabel }} <span aria-hidden="true">·</span>
            </template>
            {{ authorLabel }}
        </span>
        <span v-if="distinctReason || movement.reference" class="br-inventory__meta">
            <template v-if="distinctReason">
                <span class="colon-at-end">Motivo</span> {{ distinctReason }}
            </template>
            <template v-if="movement.reference">
                <span v-if="distinctReason" aria-hidden="true"> · </span>
                <span class="colon-at-end">Referencia</span> {{ movement.reference }}
            </template>
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
    },
    computed: {
        authorLabel() {
            const name = this.movement.user?.name;
            if(name) return `Por ${name}`;

            return this.movement.user_id ? "Usuario no disponible" : "Proceso del sistema";
        },
        distinctReason() {
            const reason = this.movement.reason?.trim() || "";
            const comparable = value => (value ?? "").trim().replace(/\.$/, "").trim().toLocaleLowerCase("es");

            return comparable(reason) === comparable(this.originLabel) ? "" : reason;
        }
    }
};
</script>
