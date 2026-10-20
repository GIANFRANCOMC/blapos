<template>
    <template v-if="['image'].includes(type)">
        <img :src="image" class="fluid-image" width="23%"/>
        <h5>
            <span class="fs-5 fw-bold px-5" v-text="text"></span>
        </h5>
    </template>
    <template v-else-if="['text'].includes(type)">
        <span v-text="text"></span>
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
        }
    },
    computed: {
        text() {

            return Constants.generalConfig.messages.withoutResults;

        },
        image() {

            return `${Constants.requestRoute}/System/assets/img/utils/without_data/empty_records.png`;

        }

    },
    methods: {
        //
    }
};
</script>
