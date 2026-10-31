<template>
    <div class="br-workspace__panel br-workspace__company br-workspace-skeleton__company" aria-hidden="true">
        <div class="br-workspace__company-head">
            <span class="br-workspace-skeleton__line br-workspace-skeleton__logo"></span>
            <span class="br-workspace-skeleton__company-head-copy">
                <span class="br-workspace-skeleton__line" style="width: 88%; height: 0.85rem"></span>
                <span class="br-workspace-skeleton__line" style="width: 67%; height: 0.65rem"></span>
            </span>
        </div>
        <div class="br-workspace__company-details">
            <div v-for="index in 3" :key="`full-${index}`" class="br-workspace__company-detail br-workspace__company-detail--full br-workspace-skeleton__detail">
                <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-icon"></span>
                <span class="br-workspace-skeleton__detail-copy">
                    <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-label"></span>
                    <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-value"></span>
                </span>
            </div>
            <div v-for="index in 4" :key="`compact-${index}`" class="br-workspace__company-detail br-workspace__company-detail--compact br-workspace-skeleton__detail br-workspace-skeleton__detail--compact">
                <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-icon"></span>
                <span class="br-workspace-skeleton__detail-copy">
                    <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-label"></span>
                    <span class="br-workspace-skeleton__line br-workspace-skeleton__detail-value"></span>
                </span>
            </div>
        </div>
    </div>
</template>
