import {createApp} from "vue";
import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import Paginator from "@System/Components/Paginator.vue";
import CodeScanner from "@System/Components/CodeScanner.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";

createApp(App)
    .component("Breadcrumb", Breadcrumb)
    .component("Paginator", Paginator)
    .component("CodeScanner", CodeScanner)
    .component("StatusBadge", StatusBadge)
    .mount("#app");
