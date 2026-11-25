<template>
    <FgButton
        v-if="compact"
        class="fg-responsive-filters__trigger"
        tone="secondary"
        :data-bs-target="'#' + id"
        data-bs-toggle="modal"
        aria-haspopup="dialog"
        :aria-controls="id">
        Filtros
    </FgButton>
    <Teleport to="body" :disabled="!compact">
        <section
            v-bind="$attrs"
            :id="id"
            ref="dialog"
            class="fg-responsive-filters"
            :class="{ 'modal fade fg-modal fg-modal--compact': compact }"
            :tabindex="compact ? -1 : undefined"
            :aria-labelledby="compact ? id + '-title' : undefined">
            <div :class="{ 'modal-dialog modal-dialog-centered': compact }">
                <div :class="{ 'modal-content fg-modal__content': compact }">
                    <div v-if="compact" class="modal-header fg-modal__header">
                        <h2 :id="id + '-title'" class="modal-title fg-modal__title">{{ label }}</h2>
                        <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar filtros">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                    <form :class="{ 'fg-responsive-filters__form--dialog': compact }" role="search" :aria-label="label" @submit.prevent="apply">
                        <div class="fg-responsive-filters__fields" :class="{ 'modal-body fg-modal__body': compact }">
                            <slot/>
                            <div v-if="!compact" class="fg-responsive-filters__actions">
                                <FgFilterActions submit clear-label="Limpiar filtros" clear-tone="secondary" :loading="loading" @clear="$emit('clear')"/>
                            </div>
                        </div>
                        <div v-if="compact" class="modal-footer fg-modal__footer">
                            <FgButton tone="secondary" data-bs-dismiss="modal">Cerrar</FgButton>
                            <div class="fg-responsive-filters__actions">
                                <FgFilterActions submit clear-label="Limpiar filtros" clear-tone="secondary" :loading="loading" @clear="$emit('clear')"/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </Teleport>
</template>

<script setup>
import {onBeforeUnmount, onMounted, ref} from "vue";
import FgButton from "./FgButton.vue";
import FgFilterActions from "./FgFilterActions.vue";

defineOptions({inheritAttrs: false});

const props = defineProps({
    id: {type: String, required: true},
    label: {type: String, required: true},
    loading: Boolean
});
const emit = defineEmits(["search", "clear"]);
const compact = ref(false);
const dialog = ref(null);
let media;
let cancelPendingLayout;

function showInline(element, modal) {
    modal?.dispose();
    element?.style.removeProperty("display");
    element?.removeAttribute("aria-hidden");
    compact.value = false;
}

function updateLayout() {
    if(media.matches) {
        compact.value = true;
        return;
    }

    const element = dialog.value;
    const modal = window.bootstrap?.Modal?.getInstance(element);
    if(modal && element.style.display !== "none") {
        if(cancelPendingLayout) return;

        // Bootstrap ignora hide() mientras termina de abrir el diálogo.
        const closeWhenShown = () => {
            if(!media.matches) modal.hide();
        };
        const finish = () => {
            cancelPendingLayout();
            if(!media.matches) showInline(element, modal);
        };
        cancelPendingLayout = () => {
            element.removeEventListener("shown.bs.modal", closeWhenShown);
            element.removeEventListener("hidden.bs.modal", finish);
            cancelPendingLayout = null;
        };
        element.addEventListener("shown.bs.modal", closeWhenShown, {once: true});
        element.addEventListener("hidden.bs.modal", finish, {once: true});
        modal.hide();
        return;
    }
    showInline(element, modal);
}

function apply() {
    if(props.loading) return;
    if(compact.value) window.bootstrap?.Modal?.getInstance(dialog.value)?.hide();
    emit("search");
}

onMounted(() => {
    media = window.matchMedia("(max-width: 767.98px)");
    updateLayout();
    media.addEventListener("change", updateLayout);
});

onBeforeUnmount(() => {
    media?.removeEventListener("change", updateLayout);
    cancelPendingLayout?.();
    window.bootstrap?.Modal?.getInstance(dialog.value)?.dispose();
});
</script>
