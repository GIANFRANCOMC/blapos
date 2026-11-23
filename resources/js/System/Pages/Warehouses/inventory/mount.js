import {createApp} from "vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputText from "@System/Components/InputText.vue";

export function mountInventoryPage(App, page) {

    createApp(App, {page})
        .component("InputNumber", InputNumber)
        .component("InputText", InputText)
        .mount("#app");

}
