import {createApp} from "vue";
import VueSelect from "vue-select";

import App from "./main.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";
import Paginator from "@System/Components/Paginator.vue";

export function mountCashPage(initialView) {

    createApp(App, {initialView})
        .component("v-select", VueSelect)
        .component("InputNumber", InputNumber)
        .component("InputSlot", InputSlot)
        .component("InputText", InputText)
        .component("Paginator", Paginator)
        .mount("#app");

}
