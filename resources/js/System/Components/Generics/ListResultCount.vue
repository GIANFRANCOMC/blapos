<template>
    <p v-if="!loading" class="br-list-result-count" aria-live="polite" aria-atomic="true">
        <span>Resultado:</span>
        <strong>{{ formatNumber(total) }}</strong>
        <span>{{ total === 1 ? singular : plural }}</span>
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
