import * as Requests from "../../Helpers/Requests.js";

const initialState = () => ({
    term: "",
    page: 0,
    hasMore: true,
    loaded: false,
    loading: false,
    requestToken: 0,
    timer: null
});

export default {
    data() {
        return {
            commercialSelection: {
                customers: initialState(),
                items: initialState()
            }
        };
    },
    beforeUnmount() {
        for(const state of Object.values(this.commercialSelection)) {
            clearTimeout(state.timer);
            state.requestToken += 1;
        }
    },
    methods: {
        openCommercialOptions(resource) {
            const state = this.commercialSelection[resource];

            if(!state.loaded && !state.loading && !state.timer) {
                this.loadCommercialOptions(resource, 1, state.requestToken);
            }
        },
        searchCommercialOptions(resource, term) {
            const state = this.commercialSelection[resource];
            const normalized = String(term || "").trim();

            if(state.term === normalized && (state.loaded || state.loading || state.timer)) return;

            clearTimeout(state.timer);
            state.term = normalized;
            state.requestToken += 1;
            state.loaded = false;
            state.hasMore = true;
            state.loading = false;

            state.timer = setTimeout(() => {
                state.timer = null;
                this.loadCommercialOptions(resource, 1, state.requestToken);
            }, 250);
        },
        loadMoreCommercialOptions(resource) {
            const state = this.commercialSelection[resource];

            if(state.loading || !state.hasMore) return;

            this.loadCommercialOptions(resource, state.page + 1, state.requestToken);
        },
        async loadCommercialOptions(resource, page, requestToken) {
            const state = this.commercialSelection[resource];
            state.loading = true;

            const result = await Requests.get({
                route: this.commercialSelectionRoute || `${Requests.config({entity: this.commercialSelectionEntity, type: "consult"})}/options`,
                data: {resource, search: state.term, page}
            });

            if(requestToken !== state.requestToken) return;

            state.loading = false;

            if(!Requests.valid({result})) return;

            const response = result.data?.data || {};
            const current = page > 1 ? (this.options?.[resource]?.records || []) : [];
            const records = [...current, ...(response.records || [])];

            this.options[resource] = {...(this.options[resource] || {}), records};

            if(resource === "customers" && this.options.holders) {
                this.options.holders.records = records;
            }

            state.page = Number(response.page || page);
            state.hasMore = Boolean(response.has_more);
            state.loaded = true;
        }
    }
};
