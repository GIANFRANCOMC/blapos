<template>
    <span ref="container" class="br-expandable-text" :class="{'br-expandable-text--expanded': expanded}">
        <span ref="content" :id="textId" class="br-expandable-text__content" v-text="text"></span>
        <button
            v-if="hasOverflow"
            type="button"
            class="br-link br-expandable-text__toggle"
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
.br-expandable-text {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
    min-inline-size: 0;
    contain: inline-size;
}

.br-expandable-text__content {
    flex: 1 1 auto;
    min-inline-size: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.br-expandable-text__toggle {
    flex: 0 0 auto;
    padding: 0;
    border: 0;
    background: transparent;
    font-size: inherit;
    line-height: inherit;
    white-space: nowrap;
}

.br-expandable-text--expanded {
    display: block;
}

.br-expandable-text--expanded .br-expandable-text__content {
    display: block;
    overflow: visible;
    overflow-wrap: anywhere;
    white-space: pre-line;
}
</style>
