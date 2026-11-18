<template>
    <ol v-if="events.length" class="br-status-roadmap" :aria-label="label">
        <li v-for="(event, index) in events" :key="event.id" class="br-status-roadmap__item"
            :aria-current="index === events.length - 1 ? 'step' : null">
            <span :class="['br-status-roadmap__marker', `text-${event.tone}`]" aria-hidden="true"></span>
            <span class="br-status-roadmap__label">{{ event.label }}</span>
            <time :datetime="event.occurred_at">{{ formatDate(event.occurred_at) }}</time>
            <span>{{ event.user_name }}</span>
        </li>
    </ol>
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
