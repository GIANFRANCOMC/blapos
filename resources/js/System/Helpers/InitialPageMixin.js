import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import PageSkeleton from "@System/Components/Generics/PageSkeleton.vue";
import {createInitialLoad} from "./InitialLoad.js";

export default {
    components: {InitialLoadTransition, PageSkeleton},
    data() {
        return {initialLoading: true, skeletonEnabled: true};
    },
    methods: {
        async loadInitialParams(loadParams) {
            const initialLoad = createInitialLoad({skeleton: true, swal: true});
            initialLoad.start();
            try {
                return await loadParams();
            } finally {
                this.initialLoading = false;
                initialLoad.finish();
                // Los formularios y modales deben existir antes de ejecutar la fase posterior.
                await this.$nextTick();
            }
        }
    }
};
