/** Estado nuevo para cada consulta; nunca comparte filas ni enlaces entre listados. */
export function emptyPagination() {
    return {data: [], total: 0, links: [], current_page: 1, last_page: 1, from: null, to: null};
}

/** Los filtros enviados ahora sustituyen los incluidos en enlaces de paginación anteriores. */
export function currentQueryRoute(route, data = {}) {
    if(typeof route !== "string" || !route.includes("?")) return route;
    const hashIndex = route.indexOf("#");
    const hash = hashIndex < 0 ? "" : route.slice(hashIndex);
    const address = hashIndex < 0 ? route : route.slice(0, hashIndex);
    const queryIndex = address.indexOf("?");
    const query = new URLSearchParams(address.slice(queryIndex + 1));
    for(const key of Object.keys(data)) {
        for(const queryKey of [...query.keys()]) {
            if(queryKey === key || queryKey.startsWith(`${key}[`)) query.delete(queryKey);
        }
    }
    const suffix = query.toString();
    return address.slice(0, queryIndex) + (suffix ? `?${suffix}` : "") + hash;
}
