import { requestRoute, generalConfig } from "./Constants.js";
import * as Requests from "./Requests.js";
import { toastrs } from "./Alerts.js";
import {formatDecimalNumber, normalizePrecision} from "../../Shared/DecimalPrecision.js";

import axios from "axios";

export function getEssential() {

    return {
        withMenu: window?.withMenu ?? true,
        ownerApp: window?.ownerApp ?? null,
        company: window?.company ?? null,
        branch: window?.branch ?? null,
        captchaSiteKey: window?.captchaSiteKey ?? "",
        publicAttendanceAccess: window?.publicAttendanceAccess ?? null
    };

}

export function navbarItem(id, {type = "active", addClass = null}) {

    try {

        document.getElementById(id).classList.add(type);

        if(isDefined({value: addClass})) {

            document.getElementById(id).classList.add(addClass);

        }

    }catch(e) {

    }

}

export function isDefined({value}) {

    return value != "" && value != null && value != undefined;

}

export function isNumber({value, minValue = 0}) {

    let number = Number(value);

    return !isNaN(number) && Number(number) >= minValue;

}

export function generateCode({length = 12}) {

    const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    let randomString = "";

    for(let i = 0; i < length; i++) {

        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters[randomIndex];

    }

    return randomString;

}

export function uuid(length = 36) {

    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)).substr(0, length);

}

export function getCurrentDate(type = "date") {

    // Obtener la fecha actual
    const currentDate = new Date();

    // Obtener el año actual
    const currentYear = currentDate.getFullYear();

    // Obtener el mes actual (agregando un cero adelante si es necesario)
    const currentMonth = ('0' + (currentDate.getMonth() + 1)).slice(-2);

    // Obtener el día del mes actual (agregando un cero adelante si es necesario)
    const currentDay = ('0' + currentDate.getDate()).slice(-2);

    const currentHour = currentDate.getHours().toString().padStart(2, '0');
    const currentMinute = currentDate.getMinutes().toString().padStart(2, '0');

    // Construir la fecha en formato "YYYY-MM-DD"
    let formattedDate = "";

    if(type == "date") {

        formattedDate = `${currentYear}-${currentMonth}-${currentDay}`;

    }else if(type = "datetime") {

        formattedDate = `${currentYear}-${currentMonth}-${currentDay}T${currentHour}:${currentMinute}`;

    }

    return formattedDate;

}

export function parseISOToDatetimeLocal(isoString) {

    // Crear un objeto Date a partir del string ISO
    const date = new Date(isoString);

    // Extraer los componentes de la fecha
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Mes (0-11) a (1-12)
    const day = String(date.getDate()).padStart(2, '0'); // Día del mes
    const hours = String(date.getHours()).padStart(2, '0'); // Hora
    const minutes = String(date.getMinutes()).padStart(2, '0'); // Minutos

    // Formatear como "YYYY-MM-DDTHH:MM"
    return `${year}-${month}-${day}T${hours}:${minutes}`;

}

export function cloneJson(json) {

    return JSON.parse(JSON.stringify(json));

}

export function calculateTotal({item}) {

    const quantity = Number(item?.quantity),
          price    = Number(item?.price);

    const total = (isNaN(quantity) || isNaN(price)) ? 0 : fixedNumber(quantity * price);

    return total;

}

export function fixedNumber(value, decimals = null) {

    return formatDecimalNumber(normalizeNumber(value), resolveDecimals(decimals));

}

export function separatorNumber(value, decimals = null) {

    return formatDecimalNumber(normalizeNumber(value), resolveDecimals(decimals), true);

}

export function resolveDecimals(decimals = null) {

    return normalizePrecision(decimals ?? generalConfig.forms.inputs.round);

}

export function normalizeNumber(value) {

    if(value === null || value === undefined || value === "") return 0;

    const normalized = typeof value === "string" ? value.trim().replace(/,/g, "") : value;
    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : 0;

}

export function truncate({value, length = 40}) {

    if(!value) return "";
    return value.length > length ? value.slice(0, length) + "..." : value;

}

export function diffDaysLegible({diff}) {

    let diffDaysLegible = "";
    let numberDiff = Number(diff);

    if(isNaN(numberDiff)) return "No identificado";

    if(numberDiff === 0) {

        diffDaysLegible = "Hoy";

    }else {

        let absNumberDiff = Math.abs(numberDiff);
        let daysLegible   = absNumberDiff > 1 ? "días" : "día";

        diffDaysLegible = `${numberDiff > 0 ? "En" : "Hace"} ${absNumberDiff} ${daysLegible}`;

    }

    return diffDaysLegible;

}

export function getErrors({errors}) {

    let propsValidate = Object.values(errors).filter(valueValidate => {

        return isDefined({value: valueValidate}) && Array.isArray(valueValidate) && valueValidate.length > 0;

    });

    return propsValidate;

}

export function addDuration({startDate, type, quantity, setEndOfDay = false}) {

    const date = new Date(startDate);

    try {

        switch(type) {
            case "hour":
                date.setHours(date.getHours() + quantity);
                break;

            case "day":
                date.setDate(date.getDate() + quantity);
                break;

            case "today":
                date.setDate(date.getDate() + (quantity <= 0 ? 0 : (quantity - 1)));
                date.setHours(23, 59, 59, 999);
                break;

            case "month":
                date.setMonth(date.getMonth() + quantity);
                break;

            case "year":
                date.setFullYear(date.getFullYear() + quantity);
                break;
        }

        if(setEndOfDay && ["day", "today", "month", "year"].includes(type)) {

            date.setHours(23, 59, 59, 999);

        }

    }catch(e) {

        date.setDate(date.getDate());

    }

    return isNaN(date.getTime()) ? "" : parseISOToDatetimeLocal(date.toString());

}


export function getSubscriptions({customer}) {

    let route = Requests.config({entity: "customers", type: "getSubscriptions"});

    return Requests.get({route: `${route}/${customer?.id}`, data: {}});

}

export function findOverlaps(sale, subscriptions) {

    const parseDate = (date) => new Date(date.replace(" ", "T"));
    const saleStart = parseDate(sale.start_date);
    const saleEnd = parseDate(sale.end_date);

    const overlappingPositions = subscriptions
        .map((subscription, index) => {
            const subscriptionStart = parseDate(subscription.start_date);
            const subscriptionEnd = parseDate(subscription.end_date);

            // Check if dates overlap
            if (
                (saleStart <= subscriptionEnd && saleStart >= subscriptionStart) || // Start is within an active subscription
                (saleEnd <= subscriptionEnd && saleEnd >= subscriptionStart) ||     // End is within an active subscription
                (saleStart <= subscriptionStart && saleEnd >= subscriptionEnd)      // Sale fully overlaps an active subscription
            ) {
                return {...subscription, keyArray: index};
            }
            return null;
        })
        .filter(index => index !== null); // Filter only positions with overlaps

    return {
        hasOverlap: overlappingPositions.length > 0,
        positions: overlappingPositions
    };

};

export function legibleFormatDate({dateString = null, type = "datetime"}) {

    try {

        if (!dateString) throw new Error("Invalid date string");

        let date;
        let year, month, day, hours = 0, minutes = 0;

        if(dateString.includes(" ")) {

            // Formato con hora (YYYY-MM-DD HH:mm)
            const [datePart, timePart] = dateString.split(" ");
            [year, month, day] = datePart.split("-").map(Number);
            [hours, minutes] = timePart.split(":").map(Number);

        }else {

            // Formato sin hora (YYYY-MM-DD)
            [year, month, day] = dateString.split("-").map(Number);

        }

        // Crear la fecha asegurando que respete la zona horaria local
        date = new Date(year, month - 1, day, hours, minutes);

        // Validar si la fecha es válida
        if(isNaN(date.getTime())) {
            throw new Error("Invalid date format. Use 'YYYY-MM-DD' or 'YYYY-MM-DD HH:mm'.");
        }

        // Extraer componentes
        const formattedDay = day.toString().padStart(2, "0");
        const formattedMonth = month.toString().padStart(2, "0");

        // Convertir hora a formato 12h
        const ampm = hours >= 12 ? "PM" : "AM";
        const formattedHours = (hours % 12 || 12).toString().padStart(2, "0");
        const formattedMinutes = minutes.toString().padStart(2, "0");

        if(type === "date") {

            return `${formattedDay}-${formattedMonth}-${year}`;

        }else if(type === "weekday_date") {

            const weekday = new Intl.DateTimeFormat("es", {weekday: "long"}).format(date);
            const weekdayCap = weekday.charAt(0).toUpperCase() + weekday.slice(1);
            return `${weekdayCap}, ${formattedDay}-${formattedMonth}-${year}`;

        }else if(type === "weekday_datetime") {

            const weekday = new Intl.DateTimeFormat("es", {weekday: "long"}).format(date);
            const weekdayCap = weekday.charAt(0).toUpperCase() + weekday.slice(1);
            return `${weekdayCap}, ${formattedDay}-${formattedMonth}-${year} · ${formattedHours}:${formattedMinutes} ${ampm}`;

        }else if (type === "datetime") {

            return `${formattedDay}-${formattedMonth}-${year} ${formattedHours}:${formattedMinutes} ${ampm}`;

        }else if (type === "time") {

            return `${formattedHours}:${formattedMinutes} ${ampm}`;

        }

        return `${formattedDay}-${formattedMonth}-${year} ${formattedHours}:${formattedMinutes} ${ampm}`;

    }catch (e) {

        return dateString; // Retorna la fecha original en caso de error

    }

}

export function sendWhatsapp({phoneNumber, message}) {

    if(!isDefined({value: phoneNumber})) {

        toastrs({type: "error", subtitle: "No es posible generar el envío a Whatsapp, diligenciar los campos necesarios."});

    }else if(!isDefined({value: message})) {

        toastrs({type: "error", subtitle: "No es posible generar el envío a Whatsapp, mensaje no identificado."});

    }else {

        let encodedMessage = encodeURIComponent(message);

        let link = "https://wa.me/"+phoneNumber+"?text="+encodedMessage;

        window.open(link, "_blank");

    }

}

export function getMessageWhatsapp({data, action}) {

    let message = null;

    if(["reportSale"].includes(action)) {

        const information = "¡Se ha creado la venta exitosamente! Para obtener el documento de la venta, visite el siguiente enlace:";
        const url = Requests.routeReport({resource: "sale", params: {document: data?.id, type: "a4"}, extras: {action}});

        message = `${information} ${url}`;

    }

    return message;

}

export function encodeBase64UTF8(text) {

    const bytes = new TextEncoder().encode(text);
    const base64 = btoa(String.fromCharCode(...bytes));

    return base64;

}

export function decodeBase64UTF8(base64) {

    const binary = atob(base64);
    const bytes = Uint8Array.from(binary, char => char.charCodeAt(0));
    const text = new TextDecoder().decode(bytes);

    return text;

}


export function playSound(soundRoute) {

    const audio = new Audio(`${Requests.config({onlyBase: true})}/System/assets/audio/utils/${soundRoute}`);

    audio.play()
    .catch(err => {

        console.warn("Error playing audio:", err);

    });

}

export function getAsset(path, {type = "storage", back = 0}) {

    if(["storage"].includes(type)) {

        const baseUrl = `/${type}/`;

        return `${baseUrl}${path}`;

    }else if(["public"].includes(type)) {

        const baseUrl = `/${type}/`;

        return `${baseUrl}${path}`;

    }else if(["none"].includes(type)) {

        return back == 1 ? `../${path}` : `${path}`;

    }

}
