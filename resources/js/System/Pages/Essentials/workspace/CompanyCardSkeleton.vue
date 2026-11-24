<template>
    <div class="fg-workspace__panel fg-workspace__company fg-workspace-skeleton__company" aria-hidden="true">
        <div class="fg-workspace__company-head">
            <span class="fg-skeleton-line fg-workspace-skeleton__logo"></span>
            <span class="fg-workspace-skeleton__company-head-copy">
                <span class="fg-skeleton-line" style="width: 88%; height: 0.85rem"></span>
                <span class="fg-skeleton-line" style="width: 67%; height: 0.65rem"></span>
            </span>
        </div>
        <div class="fg-workspace__company-details">
            <div v-for="index in 3" :key="`full-${index}`" class="fg-workspace__company-detail fg-workspace__company-detail--full fg-workspace-skeleton__detail">
                <span class="fg-skeleton-line fg-workspace-skeleton__detail-icon"></span>
                <span class="fg-workspace-skeleton__detail-copy">
                    <span class="fg-skeleton-line fg-workspace-skeleton__detail-label"></span>
                    <span class="fg-skeleton-line fg-workspace-skeleton__detail-value"></span>
                </span>
            </div>
            <div v-for="index in 4" :key="`compact-${index}`" class="fg-workspace__company-detail fg-workspace__company-detail--compact fg-workspace-skeleton__detail fg-workspace-skeleton__detail--compact">
                <span class="fg-skeleton-line fg-workspace-skeleton__detail-icon"></span>
                <span class="fg-workspace-skeleton__detail-copy">
                    <span class="fg-skeleton-line fg-workspace-skeleton__detail-label"></span>
                    <span class="fg-skeleton-line fg-workspace-skeleton__detail-value"></span>
                </span>
            </div>
        </div>
    </div>
</template>
