<template>
    <p :class="['br-list-result-count', {'br-list-result-count--loading': loading}]" aria-live="polite" aria-atomic="true" :aria-hidden="loading">
        <span>Resultado:</span>
        <strong>{{ formatNumber(total) }}</strong>
        <span>{{ total === 1 ? singular : plural }}</span>
    </p>
</template>

<script>
const formatter = new Intl.NumberFormat("es-PE");

export default {
    props: {
        pagination: {type: Object, required: true},
        loading: {type: Boolean, default: false},
        singular: {type: String, default: "registro"},
        plural: {type: String, default: "registros"}
    },
    computed: {
        total() {
            return Math.max(0, Number(this.pagination?.total) || 0);
        }
    },
    methods: {
        formatNumber(value) {
            return formatter.format(Number(value) || 0);
        }
    }
};
</script>
