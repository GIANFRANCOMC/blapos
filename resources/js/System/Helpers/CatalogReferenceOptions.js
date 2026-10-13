export function upsertCatalogOption(options, reference, record) {

    if(!record?.id) return null;

    if(!options[reference]) options[reference] = {records: []};
    if(!Array.isArray(options[reference].records)) options[reference].records = [];

    const records = options[reference].records;
    const index = records.findIndex(item => Number(item.id) === Number(record.id));

    if(index >= 0) {

        records.splice(index, 1, record);

    }else {

        records.push(record);

    }

    records.sort((first, second) =>
        String(first.name ?? "").localeCompare(String(second.name ?? ""), "es", {sensitivity: "base"})
    );

    return {code: record.id, label: record.name, data: record};

}
