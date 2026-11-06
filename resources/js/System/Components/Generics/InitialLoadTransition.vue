<template>
    <div class="br-initial-load">
        <Transition name="br-initial-load" @before-leave="hideLeavingContent">
            <slot/>
        </Transition>
    </div>
</template>

<script>
export default {
    name: "InitialLoadTransition",
    methods: {
        hideLeavingContent(element) {
            element.inert = true;
            element.setAttribute("aria-hidden", "true");
        }
    }
};
</script>
