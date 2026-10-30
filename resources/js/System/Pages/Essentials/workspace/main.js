import {createApp} from "vue";
import App from "./main.vue";
import CompanyCard from "./CompanyCard.vue";
import WorkspaceSkeleton from "./WorkspaceSkeleton.vue";
import "../../../../../css/System/Pages/Essentials/workspace/skeleton.css";

createApp(App)
    .component("CompanyCard", CompanyCard)
    .component("WorkspaceSkeleton", WorkspaceSkeleton)
    .mount("#app");
