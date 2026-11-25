import {createApp} from "vue";
import App from "./main.vue";
import Paginator from "@System/Components/Paginator.vue";

createApp(App)
    .component("Paginator", Paginator)
    .mount("#app");
