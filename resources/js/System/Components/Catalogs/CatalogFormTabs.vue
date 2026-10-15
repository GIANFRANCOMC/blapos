<template>
    <div :class="['br-entity-tabs-shell', 'br-catalog-form-tabs', {'br-catalog-form-tabs--descriptive': variant === 'descriptive'}]">
        <div class="br-catalog-form-tabs__navigation">
            <button
                v-if="variant === 'descriptive'"
                type="button"
                class="br-catalog-form-tabs__step-control"
                aria-label="Pestaña anterior"
                :disabled="activeIndex <= 0"
                @click="selectAdjacentTab(-1)">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <nav ref="tabList" class="nav nav-pills br-entity-tabs" :class="{'br-entity-tabs--five': tabs.length === 5}" role="tablist" :aria-label="label" @keydown="handleKeydown">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    :id="`${idPrefix}-tab-trigger-${tab.id}`"
                    type="button"
                    role="tab"
                    class="nav-link br-entity-tab"
                    :class="{'active is-active': modelValue === tab.id}"
                    :aria-selected="modelValue === tab.id"
                    :aria-controls="controlsPrefix ? `${controlsPrefix}-tab-${tab.id}` : undefined"
                    :tabindex="modelValue === tab.id ? 0 : -1"
                    @click="selectTab(tab.id)">
                    <span class="br-entity-tab__content">
                        <strong>
                            <span v-if="variant === 'descriptive' && tab.icon" class="br-entity-tab__icon" aria-hidden="true">
                                <i :class="tab.icon"></i>
                            </span>
                            {{ tab.label }}
                        </strong>
                        <small v-if="variant === 'descriptive' && tab.description">{{ tab.description }}</small>
                    </span>
                    <span v-if="hasErrors(tab.id)" class="br-entity-tab__error" aria-label="Contiene errores">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    </span>
                </button>
            </nav>
            <button
                v-if="variant === 'descriptive'"
                type="button"
                class="br-catalog-form-tabs__step-control"
                aria-label="Pestaña siguiente"
                :disabled="activeIndex >= tabs.length - 1"
                @click="selectAdjacentTab(1)">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: "CatalogFormTabs",
    emits: ["update:modelValue"],
    props: {
        tabs: {type: Array, required: true},
        modelValue: {type: String, required: true},
        label: {type: String, required: true},
        idPrefix: {type: String, required: true},
        controlsPrefix: {type: String, default: ""},
        variant: {type: String, default: "compact"},
        hasErrors: {type: Function, default: () => false}
    },
    computed: {
        activeIndex() {

            return this.tabs.findIndex(tab => tab.id === this.modelValue);

        }
    },
    watch: {
        modelValue() {

            this.$nextTick(() => {

                const tabList = this.$refs.tabList;
                const selected = tabList?.querySelector('[aria-selected="true"]');

                if(!tabList || !selected || tabList.scrollWidth <= tabList.clientWidth) {
                    return;
                }

                const left = selected.offsetLeft - tabList.offsetLeft;
                const right = left + selected.offsetWidth;

                if(left < tabList.scrollLeft) {
                    tabList.scrollTo({left, behavior: "auto"});
                } else if(right > tabList.scrollLeft + tabList.clientWidth) {
                    tabList.scrollTo({left: right - tabList.clientWidth, behavior: "auto"});
                }

            });

        }
    },
    methods: {
        selectAdjacentTab(direction) {

            const nextIndex = this.activeIndex + direction;

            if(nextIndex < 0 || nextIndex >= this.tabs.length) {

                return;

            }

            this.selectTab(this.tabs[nextIndex].id);
            this.$nextTick(() => this.$refs.tabList?.querySelector('[aria-selected="true"]')?.focus());

        },
        selectTab(tabId) {

            this.$emit("update:modelValue", tabId);

        },
        handleKeydown(event) {

            const index = this.tabs.findIndex(tab => tab.id === this.modelValue);
            let nextIndex;

            if(event.key === "ArrowRight") {
                nextIndex = (index + 1) % this.tabs.length;
            } else if(event.key === "ArrowLeft") {
                nextIndex = (index - 1 + this.tabs.length) % this.tabs.length;
            } else if(event.key === "Home") {
                nextIndex = 0;
            } else if(event.key === "End") {
                nextIndex = this.tabs.length - 1;
            } else {
                return;
            }

            event.preventDefault();
            this.selectTab(this.tabs[nextIndex].id);

            this.$nextTick(() => {

                this.$refs.tabList?.querySelector('[aria-selected="true"]')?.focus();

            });

        }
    }
};
</script>
