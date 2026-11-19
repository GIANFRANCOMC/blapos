<template>
    <table v-if="events.length" class="br-status-roadmap" :aria-label="label">
        <thead>
            <tr>
                <th scope="col">Movimiento</th>
                <th scope="col">Fecha de registro</th>
                <th scope="col">Responsable</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(event, index) in events" :key="event.id" class="br-status-roadmap__item"
                :aria-current="index === events.length - 1 ? 'step' : null">
                <th scope="row" class="br-status-roadmap__label">
                    <span :class="['br-status-roadmap__marker', `text-${event.tone}`]" aria-hidden="true"></span>
                    {{ event.label }}
                </th>
                <td><time :datetime="event.occurred_at">{{ formatDate(event.occurred_at) }}</time></td>
                <td>{{ event.user_name }}</td>
            </tr>
        </tbody>
    </table>
    <p v-else class="text-muted mb-0">No hay cambios de estado registrados.</p>
</template>

<script>
export default {
    props: {
        events: {type: Array, default: () => []},
        label: {type: String, default: "Historial de estados"},
        formatDate: {type: Function, required: true}
    }
};
</script>
