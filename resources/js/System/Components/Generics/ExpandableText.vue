<template>
    <span ref="container" class="br-expandable-text br-inline-text-action" :class="{'br-expandable-text--expanded': expanded}">
        <span ref="content" :id="textId" class="br-inline-text-action__content" v-text="text"></span>
        <button
            v-if="hasOverflow"
            type="button"
            class="br-link br-inline-text-action__button"
            :aria-controls="textId"
            :aria-expanded="expanded"
            @click.stop="toggle">
            {{ expanded ? "Ver menos" : "Ver más" }}
        </button>
    </span>
</template>

<script>
import {useId} from "vue";

export default {
    name: "ExpandableText",
    props: {
        text: {type: String, required: true}
    },
    setup() {
        return {textId: `expandable-text-${useId()}`};
    },
    data() {
        return {expanded: false, hasOverflow: false, resizeObserver: null};
    },
    mounted() {
        this.measureOverflow();
        if(typeof ResizeObserver !== "undefined") {
            this.resizeObserver = new ResizeObserver(this.measureOverflow);
            this.resizeObserver.observe(this.$refs.container);
        }
        document.fonts?.ready.then(this.measureOverflow);
    },
    beforeUnmount() {
        this.resizeObserver?.disconnect();
    },
    watch: {
        text() {
            this.expanded = false;
            this.$nextTick(this.measureOverflow);
        }
    },
    methods: {
        measureOverflow() {
            const {content, container} = this.$refs;
            if(this.expanded || !container || !content) return;

            // Use the full row width so the toggle cannot keep itself visible.
            this.hasOverflow = content.scrollWidth > container.clientWidth;
        },
        toggle() {
            this.expanded = !this.expanded;
            if(!this.expanded) this.$nextTick(this.measureOverflow);
        }
    }
};
</script>

<style scoped>
.br-expandable-text--expanded {
    display: block;
}

.br-expandable-text--expanded .br-inline-text-action__content {
    display: block;
    overflow: visible;
    overflow-wrap: anywhere;
    white-space: pre-line;
}
</style>
