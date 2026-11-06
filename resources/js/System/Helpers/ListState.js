/** Estado nuevo para cada consulta; nunca comparte filas ni enlaces entre listados. */
export function emptyPagination() {
    return {data: [], total: 0, links: [], current_page: 1, last_page: 1, from: null, to: null};
}
