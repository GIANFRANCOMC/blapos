<template>
    <div class="br-tabs" :class="{ 'br-tabs--descriptive': variant === 'descriptive', 'br-tabs--content': layout === 'content' }">
        <button v-if="variant === 'descriptive'" type="button" class="br-tabs__step"
            aria-label="Pestaña anterior" :disabled="activeIndex <= 0" @click="selectAdjacent(-1)">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <nav ref="tabList" class="br-tabs__list" role="tablist" :aria-label="label" @keydown="handleKeydown">
            <button v-for="tab in tabs" :key="tab.id" :id="`${idPrefix}-tab-trigger-${tab.id}`"
                type="button" role="tab" class="br-tabs__item"
                :aria-selected="modelValue === tab.id"
                :aria-controls="sharedControlsId || (controlsPrefix ? `${controlsPrefix}-tab-${tab.id}` : undefined)"
                :tabindex="modelValue === tab.id ? 0 : -1"
                @click="selectTab(tab.id)">
                <span v-if="variant === 'descriptive' && tab.icon" class="br-tabs__icon" aria-hidden="true">
                    <i :class="tab.icon"></i>
                </span>
                <span class="br-tabs__text">
                    <span class="br-tabs__label">{{ tab.label }}</span>
                    <small v-if="variant === 'descriptive' && tab.description" class="br-tabs__description">{{ tab.description }}</small>
                </span>
                <span v-if="hasErrors(tab.id)" class="br-tabs__error">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span class="visually-hidden">Contiene errores</span>
                </span>
            </button>
        </nav>
        <button v-if="variant === 'descriptive'" type="button" class="br-tabs__step"
            aria-label="Pestaña siguiente" :disabled="activeIndex >= tabs.length - 1" @click="selectAdjacent(1)">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>
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
