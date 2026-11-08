import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import PageSkeleton from "@System/Components/Generics/PageSkeleton.vue";

export default {
    components: {InitialLoadTransition, PageSkeleton},
    data() {
        return {initialLoading: true};
    },
    methods: {
        async loadInitialParams(loadParams) {
            try {
                return await loadParams();
            } finally {
                this.initialLoading = false;
                // Los formularios y modales deben existir antes de ejecutar la fase posterior.
                await this.$nextTick();
            }
        }
    }
};
