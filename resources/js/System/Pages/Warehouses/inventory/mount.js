import {createApp} from "vue";
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import App from "./InventoryPage.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import InputDate from "@System/Components/InputDate.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputSelect from "@System/Components/InputSelect.vue";
import InputSelect2 from "@System/Components/InputSelect2.vue";
import InputText from "@System/Components/InputText.vue";
import Paginator from "@System/Components/Paginator.vue";
import Loader from "@System/Components/Loader.vue";
import WithoutData from "@System/Components/WithoutData.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";
import SelectNoOptions from "@System/Components/Generics/SelectNoOptions.vue";

export function mountInventoryPage(page) {

    createApp(App, {page})
        .component("v-select", VueSelect)
        .component("Breadcrumb", Breadcrumb)
        .component("InputDate", InputDate)
        .component("InputNumber", InputNumber)
        .component("InputSelect", InputSelect)
        .component("InputSelect2", InputSelect2)
        .component("InputText", InputText)
        .component("Paginator", Paginator)
        .component("Loader", Loader)
        .component("WithoutData", WithoutData)
        .component("StatusBadge", StatusBadge)
        .component("SelectNoOptions", SelectNoOptions)
        .mount("#app");

}
