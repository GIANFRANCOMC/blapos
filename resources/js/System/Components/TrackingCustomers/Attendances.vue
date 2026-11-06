<template>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="text-center align-middle">
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 10%;"></th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 45%;">SUCURSAL</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 25%;">INGRESO</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 25%;">SALIDA</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <template v-if="data.length > 0">
                    <tr v-for="record in data" :key="record.id" class="text-center">
                        <td :class="[{ 'br-status-surface--success': ['active'].includes(record.status), 'br-status-surface--primary': ['finalized'].includes(record.status), 'br-status-surface--danger': ['canceled'].includes(record.status) }]">
                            <span class="d-block fw-bold small" v-text="record.formatted_status"></span>
                        </td>
                        <td class="text-start">
                            <span v-text="record.branch?.name" class="fw-bold d-block"></span>
                            <span v-text="record.branch?.address" class="d-block"></span>
                        </td>
                        <td>
                            <template v-if="isDefined({value: record.start_date})">
                                <span v-text="legibleFormatDate({dateString: record.start_date, type: 'date'})" class="d-block fw-semibold"></span>
                                <span v-text="legibleFormatDate({dateString: record.start_date, type: 'time'})" class="d-block fw-semibold"></span>
                            </template>
                            <span v-else class="d-block fw-bold text-dark">Pendiente</span>
                        </td>
                        <td :class="[{ 'br-status-surface--secondary': ['active'].includes(record.status) }]">
                            <template v-if="isDefined({value: record.end_date})">
                                <span v-text="legibleFormatDate({dateString: record.end_date, type: 'date'})" class="d-block fw-semibold"></span>
                                <span v-text="legibleFormatDate({dateString: record.end_date, type: 'time'})" class="d-block fw-semibold"></span>
                            </template>
                            <span v-else class="d-block fw-bold text-dark">Pendiente</span>
                        </td>
                    </tr>
                </template>
                <template v-else>
                    <tr class="br-table-state">
                        <td class="text-center" colspan="99">
                            <WithoutData type="image"/>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>

<script>
import * as Utils  from "../../Helpers/Utils.js";

export default {
    name: "Attendances",
    emits: [],
    props: {
        data: {
            type: Array,
            required: false,
            default: []
        }
    },
    computed: {
        //
    },
    methods: {
        // Others
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        }
    }
};
</script>
