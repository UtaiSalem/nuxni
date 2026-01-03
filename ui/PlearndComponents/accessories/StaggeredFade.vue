<script setup>
import { onMounted } from 'vue';

let Velocity = null;

onMounted(async () => {
    if (typeof window !== 'undefined') {
        const module = await import("velocity-animate");
        Velocity = module.default || module;
    }
});

const props = defineProps({
    duration: {
      type: Number,
      default: 200 // duration of each element transition
    }
});

function beforeEnter(el) {
    el.style.opacity = 0;
    el.style.height = 0;
}

function enter(el, done) {
    // Each element requires a data-index attribute in order for the transition to work properly
    const index = el.dataset.index || 1;
    var delay = index * props.duration;
    setTimeout(() => {
        if (Velocity) {
            Velocity(el, { opacity: 1, height: "100%" }, { complete: done });
        } else {
            el.style.opacity = 1;
            el.style.height = "100%";
            done();
        }
    }, delay);
}

function leave(el, done) {
    // Each element requires a data-index attribute in order for the transition to work properly
    const index = el.dataset.index || 1;
    var delay = index * props.duration;
    setTimeout(() => {
        if (Velocity) {
            Velocity(el, { opacity: 0, height: 0 }, { complete: done });
        } else {
            el.style.opacity = 0;
            el.style.height = 0;
            done();
        }
    }, delay);
}

</script>
<template>
    <transition-group
      name="staggered-fade"
      :css="false"
      appear
      v-bind="$attrs"
      @before-enter="beforeEnter"
      @enter="enter"
      @leave="leave"
    >
      <!-- Each element requires a data-index attribute in order for the transition to work properly -->
      <slot></slot>
    </transition-group>
  </template>
