<template>
    <div class="fg-initial-load">
        <Transition name="fg-initial-load" @before-leave="hideLeavingContent">
            <slot/>
        </Transition>
    </div>
</template>

<script>
import "../../../../css/Shared/skeleton.css";
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
