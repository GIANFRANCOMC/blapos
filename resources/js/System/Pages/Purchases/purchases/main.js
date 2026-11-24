import {createApp} from "vue";
import VueSelect from "vue-select";

import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import AddSupplier from "@System/Components/Purchases/AddSupplier.vue";
import InputDate from "@System/Components/InputDate.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import Loader from "@System/Components/Loader.vue";
import Paginator from "@System/Components/Paginator.vue";
import WithoutData from "@System/Components/WithoutData.vue";

createApp(App)
    .component("v-select", VueSelect)
    .component("AddSupplier", AddSupplier)
    .component("Breadcrumb", Breadcrumb)
    .component("InputDate", InputDate)
    .component("InputNumber", InputNumber)
    .component("InputSlot", InputSlot)
    .component("InputText", InputText)
    .component("InputTextArea", InputTextArea)
    .component("Loader", Loader)
    .component("Paginator", Paginator)
    .component("WithoutData", WithoutData)
    .mount("#app");
