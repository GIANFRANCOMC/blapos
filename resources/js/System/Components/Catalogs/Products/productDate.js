export function productToday(timeZone = 'America/Lima', now = new Date()) {
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en', {timeZone, year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(now).map(({type, value}) => [type, value]));
    return `${parts.year}-${parts.month}-${parts.day}`;
}

export function validateProductExpiry(result, {expires_at}, {isUpdate, today}) {
    if(!isUpdate && expires_at && String(expires_at).slice(0, 10) < today) {
        result.errors.expires_at = ['Elige hoy o una fecha futura.'];
        result.bool = false;
    }
}
