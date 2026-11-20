import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";

export async function downloadCatalogList({resource, route, filters, fileName}) {
    const word = filters.word?.trim();
    const scope = word
        ? `El Excel incluye todos los resultados de la búsqueda «${word}».`
        : `El Excel incluye el listado completo de ${resource}.`;
    const confirmed = await Alerts.confirmAction({
        title: `¿Descargar el listado de ${resource}?`,
        text: scope,
        confirmButtonText: "Descargar Excel"
    });
    if(!confirmed) return;

    Alerts.loading({title: `Preparando el Excel de ${resource}`});
    let result;
    try {
        result = await Requests.download({route, data: filters, fileName, showAlert: true});
    } catch {
        Alerts.toastrs({type: "error", title: "No se pudo preparar el Excel", subtitle: "Vuelve a intentar la descarga."});
    } finally {
        Alerts.close();
    }

    if(result?.bool) Alerts.toastrs({type: "success", title: "Descarga iniciada", subtitle: `Consulta el Excel de ${resource} en las descargas del navegador.`});
}
