import { createApp } from "vue";
import VueSelect from "vue-select";

import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import Loader from "@System/Components/Loader.vue";
import Paginator from "@System/Components/Paginator.vue";
import WithoutData from "@System/Components/WithoutData.vue";

createApp(App)
    .component("v-select", VueSelect)
    .component("Breadcrumb", Breadcrumb)
    .component("Loader", Loader)
    .component("Paginator", Paginator)
    .component("WithoutData", WithoutData)
    .mount("#app");
