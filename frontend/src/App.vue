<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
    <main class="app-layout">
        <RouterView />
    </main>
</template>

<style>


#app {
    width: 100%;
    min-width: 0;
}

.app-layout {
    width: 100%;
    min-width: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: flex-start;
}
</style>
