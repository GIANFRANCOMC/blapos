import axios from "axios";

const SEARCH_DELAY = 250;

export async function fetchOptions({origin, entity, search = "", page = 1}, signal) {
    const url = `/options/${encodeURIComponent(origin)}/${encodeURIComponent(entity)}`;
    const {data: response} = await axios.get(url, {
        params: {search: String(search ?? "").trim(), page},
        headers: {Accept: "application/json"},
        signal,
        timeout: 20000
    });

    return response?.bool && Array.isArray(response.data?.records) ? response.data : null;
}

export function createOptionSearch(...entities) {
    return {
        data() {
            return {
                remoteOptions: Object.fromEntries(entities.map(entity => [entity, {
                    term: "", page: 0, hasMore: false, loaded: false, loading: false, timer: null, controller: null
                }]))
            };
        },
        beforeUnmount() {
            for(const state of Object.values(this.remoteOptions)) {
                clearTimeout(state.timer);
                const controller = state.controller;
                state.controller = null;
                controller?.abort();
            }
        },
        methods: {
            openOptions(entity) {
                const state = this.remoteOptions[entity];
                if(!state.loaded && !state.loading) return this.loadOptions(entity, 1);
            },
            searchOptions(entity, term) {
                const state = this.remoteOptions[entity];
                const search = String(term ?? "").trim();
                if(state.term === search && (state.loaded || state.loading)) return;

                clearTimeout(state.timer);
                const controller = state.controller;
                state.controller = null;
                controller?.abort();
                Object.assign(state, {term: search, page: 0, loaded: false, hasMore: false, loading: true});

                state.timer = setTimeout(() => {
                    state.timer = null;
                    this.loadOptions(entity, 1);
                }, SEARCH_DELAY);
            },
            loadMoreOptions(entity) {
                const state = this.remoteOptions[entity];
                if(state.hasMore && !state.loading) return this.loadOptions(entity, state.page + 1);
            },
            async loadOptions(entity, page) {
                const state = this.remoteOptions[entity];
                const controller = new AbortController();
                state.controller = controller;
                state.loading = true;

                try {
                    const result = await fetchOptions({origin: this.optionOrigin, entity, search: state.term, page}, controller.signal);
                    if(state.controller !== controller || !result) return;

                    const previous = this.options[entity];
                    const records = page === 1 ? result.records : [...(previous?.records ?? []), ...result.records];
                    this.options[entity] = {...previous, records};
                    Object.assign(state, {page: result.page ?? page, loaded: true, hasMore: Boolean(result.has_more)});
                } catch(error) {
                    // Mantener las opciones visibles y permitir reintentar al abrir o buscar.
                } finally {
                    if(state.controller === controller) {
                        state.controller = null;
                        state.loading = false;
                    }
                }
            }
        }
    };
}

export function select2Ajax({origin, entity}) {
    let activeRequest;

    return {
        delay: SEARCH_DELAY,
        data: ({term, page}) => ({search: term, page}),
        abort() {
            activeRequest?.abort();
        },
        transport({data}, success, failure) {
            const controller = new AbortController();
            activeRequest = controller;
            fetchOptions({origin, entity, ...data}, controller.signal)
                .then(result => {
                    if(!controller.signal.aborted) return result ? success(result) : failure();
                })
                .catch(error => {
                    if(!controller.signal.aborted) failure(error);
                })
                .finally(() => {
                    if(activeRequest === controller) activeRequest = null;
                });
            return controller;
        },
        processResults(result) {
            return {
                results: result.records.map(record => ({
                    ...record,
                    id: record.id ?? record.code,
                    text: record.label ?? record.name ?? record.text
                })),
                pagination: {more: Boolean(result.has_more)}
            };
        }
    };
}
