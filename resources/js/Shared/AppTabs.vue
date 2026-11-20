<template>
    <nav  class="fg-tabs" :aria-label="label">
        <div ref="tabList" class="fg-tabs__list" role="tablist" :aria-label="label" aria-orientation="vertical" @keydown="handleKeydown">
            <button v-for="tab in tabs" :key="tab.id" :id="idPrefix + '-tab-trigger-' + tab.id" class="fg-tabs__item" type="button" role="tab"
                :aria-selected="modelValue === tab.id" :aria-controls="sharedControlsId || (controlsPrefix ? controlsPrefix + '-tab-' + tab.id : idPrefix + '-panel')" :tabindex="modelValue === tab.id ? 0 : -1" @click="selectTab(tab.id)">
                <i v-if="tab.icon" :class="tab.icon" aria-hidden="true"/><span>{{ tab.label }}</span>
                <i v-if="hasErrors(tab.id)" class="fa-solid fa-circle-exclamation fg-required" aria-label="Contiene errores"/>
            </button>
        </div>
    </nav>

</template>

<script>
export default {
    name: "AppTabs",
    emits: ["update:modelValue"],
    props: {
        tabs: {type: Array, required: true},
        modelValue: {type: String, required: true},
        label: {type: String, required: true},
        idPrefix: {type: String, required: true},
        controlsPrefix: {type: String, default: ""},
        sharedControlsId: {type: String, default: ""},
        variant: {type: String, default: "compact", validator: value => ["compact", "descriptive"].includes(value)},
        layout: {type: String, default: "stretch", validator: value => ["stretch", "content"].includes(value)},
        hasErrors: {type: Function, default: () => false}
    },
    computed: {
        activeIndex() {
            return this.tabs.findIndex(tab => tab.id === this.modelValue);
        }
    },
    mounted() {
        this.revealSelected();
    },
    watch: {
        modelValue() {
            this.$nextTick(this.revealSelected);
        }
    },
    methods: {
        selectedTab() {
            return this.$refs.tabList?.querySelector('[aria-selected="true"]');
        },
        revealSelected() {
            const list = this.$refs.tabList;
            const selected = this.selectedTab();
            if(!selected || list.scrollWidth <= list.clientWidth) return;

            const viewport = list.getBoundingClientRect();
            const tab = selected.getBoundingClientRect();
            if(tab.left < viewport.left - 1) {
                list.scrollBy({left: tab.left - viewport.left, behavior: "instant"});
            } else if(tab.right > viewport.right + 1) {
                list.scrollBy({left: tab.right - viewport.right, behavior: "instant"});
            }
        },
        selectTab(id) {
            if(id !== this.modelValue) this.$emit("update:modelValue", id);
        },
        selectAdjacent(direction) {
            this.selectAndFocus(this.activeIndex + direction);
        },
        selectAndFocus(index) {
            const next = this.tabs[index];
            if(!next) return;

            this.selectTab(next.id);
            this.$nextTick(() => this.selectedTab()?.focus({preventScroll: true}));
        },
        handleKeydown(event) {
            const count = this.tabs.length;
            if(!count) return;

            const indices = {
                ArrowDown: (this.activeIndex + 1) % count,
                ArrowUp: (this.activeIndex + count - 1) % count,
                ArrowRight: (this.activeIndex + 1) % count,
                ArrowLeft: (this.activeIndex + count - 1) % count,
                Home: 0,
                End: count - 1
            };
            if(!Object.hasOwn(indices, event.key)) return;

            event.preventDefault();
            this.selectAndFocus(indices[event.key]);
        }
    }
};
</script>
