<script setup>
// No imports needed
</script>

<template>
    <div class="flex flex-col items-center justify-center w-full py-20">
        <div class="comet-spinner mb-4"></div>
        <span class="text-gray-400 text-sm animate-pulse">กำลังโหลดข้อมูล...</span>
    </div>
</template>

<style scoped>
.comet-spinner {
    width: 48px;
    height: 48px;
    position: relative;
    animation: spin 1s linear infinite;
}

.comet-spinner::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 50%;
    padding: 3px; /* Thickness of the ring */
    background: conic-gradient(from 0deg at 50% 50%, transparent 0%, #8b5cf6 50%, #06b6d4 100%);
    /* Mask to show only the border (padding area) */
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
}

.comet-spinner::after {
    content: "";
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 6px;
    height: 6px;
    background: #06b6d4;
    border-radius: 50%;
    box-shadow: 0 0 12px 2px #06b6d4; /* Glowing effect */
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
