<template>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr class="text-center align-middle">
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 10%;"></th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 40%;">SUCURSAL</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 25%;">FECHA DE INICIO</th>
                    <th class="bg-secondary text-white fw-semibold min-w-150px" style="width: 25%;">FECHA DE FINALIZACIÓN</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <template v-if="data.length > 0">
                    <tr v-for="record in data" :key="record.id" class="text-center">
                        <td>
                            <StatusBadge
                                :status="record.status"
                                :formatted-status="record.formatted_status"
                                :custom-variants="statusBadgeSubscriptionVariants"/>
                        </td>
                        <td class="text-start">
                            <span v-text="record.branch?.name" class="fw-bold d-block"></span>
                            <span v-text="record.branch?.address" class="d-block"></span>
                        </td>
                        <td>
                            <span v-text="legibleFormatDate({dateString: record.start_date, type: 'date'})" class="d-block fw-semibold"></span>
                            <span v-text="legibleFormatDate({dateString: record.start_date, type: 'time'})" class="d-block fw-semibold"></span>
                        </td>
                        <td>
                            <span v-text="legibleFormatDate({dateString: record.end_date, type: 'date'})" class="d-block fw-semibold"></span>
                            <span v-text="legibleFormatDate({dateString: record.end_date, type: 'time'})" class="d-block fw-semibold"></span>
                            <small
                                v-if="record.remaining_time_label"
                                :class="['d-block fw-semibold mt-1', remainingTimeClass(record)]"
                                v-text="record.remaining_time_label"></small>
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
import {STATUS_BADGE_CUSTOM_SUBSCRIPTION} from "@System/Helpers/ModuleConstants.js";

export default {
    name: "Subscriptions",
    emits: [],
    props: {
        data: {
            type: Array,
            required: false,
            default: []
        }
    },
    computed: {
        statusBadgeSubscriptionVariants() {
            return STATUS_BADGE_CUSTOM_SUBSCRIPTION;
        }
    },
    methods: {
        // Others
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        },
        remainingTimeClass(record = {}) {

            const days = Number(record?.remaining_days ?? 0);

            if(days < 0) return "text-danger";
            if(days <= 3) return "text-warning";

            return "text-success";

        }
    }
};
</script>
