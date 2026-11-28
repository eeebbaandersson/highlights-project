<script setup>
import { ref } from 'vue'
import { RouterLink } from "vue-router";


const subTitle = ref('A year goes by fast, and without a place to capture it, even our best moments are easily forgotten. What is worth remembering or what you choose to capture, is entirely up to you.')
</script>

<template>
    <div class="hero-container">
        <div class="hero-badge">THE LIVING ARCHIVE</div>
        <h1>The <span class="highlight-word">Highlights</span> Project</h1>
        <p class="hero-tagline">Small wins. Things that matter. Yours to keep.</p>

        <p class="hero-description">
            {{ subTitle }}
        </p>

        <div class="hero-actions">
            <RouterLink :to="{ name: 'timeline' }" class="primary-btn">
                Get Started
            </RouterLink>
        </div>
    </div>
</template>

<style scoped>
.hero-container {
    box-sizing: border-box;
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 4rem 1rem;
}

.hero-badge {
    font-size: 0.75rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #67e8f9;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

h1 {
    font-size: 4rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}

.highlight-word {
    position: relative;
    display: inline-block;
    z-index: 1;
}

.highlight-word::after {
    content: '';
    position: absolute;
    left: -6px;
    right: -6px;
    bottom: 6px;
    height: 3px;
    z-index: -1;
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(103, 232, 249, 0.35) 25%,
        rgba(103, 232, 249, 0.85) 50%,
        rgba(103, 232, 249, 0.35) 75%,
        transparent 100%
    );
    filter: blur(0.5px);
    box-shadow: 0 0 14px rgba(103, 232, 249, 0.45);
}

.hero-tagline {
    font-size: 0.9rem;
    color: rgb(255 255 255 / 0.60);
    margin-bottom: 1rem;
    letter-spacing: 0.08em;

}

.hero-description {
    font-size: 1.15rem;
    color: rgb(255 255 255 / 0.82);
    line-height: 1.6;
    max-width: 600px;
    margin-bottom: 2.5rem;
}

.primary-btn {
    padding: 0.85rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    letter-spacing: 1px;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: rgba(103, 232, 249, 0.1);
    border: 1px solid #67e8f9;
    color: #67e8f9;
    display: inline-block;
    text-decoration: none;
}

.primary-btn:hover {
    box-shadow: 0 0 20px rgba(103, 232, 249, 0.2);

}
</style>
