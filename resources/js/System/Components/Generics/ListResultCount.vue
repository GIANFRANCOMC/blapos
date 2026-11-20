<template>
    <p v-if="!loading" class="fg-help fg-result-count" aria-live="polite" aria-atomic="true">
        Mostrando {{ formatNumber(from) }}–{{ formatNumber(to) }} de {{ formatNumber(total) }} {{ total === 1 ? singular : plural }}
    </p>
</template>

<script>
import {normalizeNumber, separatorNumber} from "../../../Shared/Numbers.js";

export default {
    props: {
        pagination: {type: Object, required: true},
        loading: {type: Boolean, default: false},
        singular: {type: String, default: "registro"},
        plural: {type: String, default: "registros"}
    },
    computed: {
        from() { return Math.min(this.total, Math.max(0, Number(this.pagination?.from ?? (this.total ? 1 : 0)))); },
        to() { return Math.min(this.total, Math.max(0, Number(this.pagination?.to ?? this.pagination?.data?.length ?? this.total))); },
        total() {
            return Math.max(0, normalizeNumber(this.pagination?.total));
        }
    },
    methods: {
        formatNumber(value) {
            return separatorNumber(value, 0, "es-PE");
        }
    }
};
</script>
