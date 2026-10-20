<template>
    <div v-if="type === 'image'" class="br-feedback-state br-empty-state" role="status">
        <img
            v-if="imageSrc"
            :src="imageSrc"
            alt=""
            class="br-empty-state__custom-image"/>
        <svg
            v-else
            class="br-empty-state__illustration"
            viewBox="0 0 120 88"
            aria-hidden="true">
            <path class="br-empty-state__surface" d="M20 30h80v45H20z"/>
            <path class="br-empty-state__folder" d="M25 25h27l7 8h36v38H25z"/>
            <path class="br-empty-state__sheet" d="M42 15h39v43H42z"/>
            <path class="br-empty-state__line" d="M51 27h21M51 35h16M51 43h19"/>
            <circle class="br-empty-state__search" cx="83" cy="60" r="12"/>
            <path class="br-empty-state__search-handle" d="m92 69 9 9"/>
        </svg>
        <div class="br-feedback-state__content">
            <strong class="br-feedback-state__title" v-text="text"></strong>
            <small
                v-if="description"
                class="br-feedback-state__description"
                v-text="description"></small>
        </div>
    </div>
    <template v-else-if="type === 'text'">
        <span class="br-feedback-state__description" v-text="text"></span>
    </template>
</template>

<script>
import * as Constants  from "../Helpers/Constants.js";

export default {
    name: "WithoutData",
    emits: [],
    props: {
        type: {
            type: String,
            required: false,
            default: "image"
        },
        text: {
            type: String,
            required: false,
            default: Constants.generalConfig.messages.withoutResults
        },
        imageSrc: {
            type: String,
            required: false,
            default: "/System/assets/img/utils/without_data/empty_records.png"
        },
        description: {
            type: String,
            required: false,
            default: "Aún no hay información para mostrar."
        }
    }
};
</script>
