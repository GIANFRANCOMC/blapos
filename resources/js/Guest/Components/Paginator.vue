<template>
    <nav aria-label="Page navigation" class="my-3" v-if="!loading && links.length > 0">
        <ul class="pagination d-flex flex-wrap">
            <template v-for="(link, key) in links" :key="key">
                <li :class="['page-item my-1', link.active ? 'active' : (link.url ? '' : 'disabled')]">
                    <a :class="['page-link waves-effect mx-1 px-3', link?.active ? 'disabled' : '']" href="javascript:void(0);" @click="handleClickPage({url: link.url})" v-html="link.label"></a>
                </li>
            </template>
        </ul>
    </nav>
</template>

<script>
export default {
    name: "Paginator",
    emits: ["clickPage"],
    props: {
        loading: {type: Boolean, default: false},
        links: {
            type: Array,
            required: false,
            default: []
        }
    },
    computed: {
        //
    },
    methods: {
        handleClickPage({url}) {

            if(this.loading || !url) return;

            this.$emit("clickPage", {url});

        }
    }
};
</script>
