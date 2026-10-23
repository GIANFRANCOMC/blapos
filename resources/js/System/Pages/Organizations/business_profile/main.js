import {createApp} from "vue";
import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import Loader from "@System/Components/Loader.vue";
import WithoutData from "@System/Components/WithoutData.vue";

createApp(App)
    .component("Breadcrumb", Breadcrumb)
    .component("Loader", Loader)
    .component("WithoutData", WithoutData)
    .mount("#app");
