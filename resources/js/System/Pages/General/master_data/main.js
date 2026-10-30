import { createApp } from "vue";
import App from "./main.vue";
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import Breadcrumb from "@System/Components/Breadcrumb.vue";
import Loader from "@System/Components/Loader.vue";
import WithoutData from "@System/Components/WithoutData.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";
import "../../../../../css/System/Pages/General/master_data/skeleton.css";

createApp(App)
    .component("v-select", VueSelect)
    .component("Breadcrumb", Breadcrumb)
    .component("Loader", Loader)
    .component("WithoutData", WithoutData)
    .component("StatusBadge", StatusBadge)
    .mount("#app");
