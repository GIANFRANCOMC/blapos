<template>
    <div
        ref="viewport"
        class="fg-table-scroll"
        :tabindex="canScroll ? 0 : undefined"
        :role="canScroll ? 'region' : undefined"
        :aria-label="canScroll ? (scrollY ? 'Desplazamiento de la tabla' : 'Desplazamiento horizontal de la tabla') : undefined">
        <slot/>
    </div>
</template>

<script>
export default {
    name: "ResponsiveTable",
    props: {scrollY: Boolean},
    data() {
        return {canScroll: false};
    },
    mounted() {
        const viewport = this.$refs.viewport;
        this.measureScroll();
        this.resizeObserver = new ResizeObserver(this.measureScroll);
        this.resizeObserver.observe(viewport);
        if(viewport.firstElementChild) this.resizeObserver.observe(viewport.firstElementChild);
    },
    updated() {
        this.measureScroll();
    },
    beforeUnmount() {
        this.resizeObserver.disconnect();
    },
    methods: {
        measureScroll() {
            const viewport = this.$refs.viewport;
            this.canScroll = viewport.scrollWidth > viewport.clientWidth
                || (this.scrollY && viewport.scrollHeight > viewport.clientHeight);
        }
    }
};
</script>
