<template>
  <transition-group
    tag="ul"
    :css="false"
    @before-enter="beforeEnter"
    @enter="enter"
    @leave="leave"
  >
    <slot></slot>
  </transition-group>
</template>

<script setup>
import gsap from 'gsap'

const beforeEnter = (el) => {
  el.style.opacity = 0
  el.style.transform = 'translateY(20px)'
}

const enter = (el, done) => {
  gsap.to(el, {
    opacity: 1,
    y: 0,
    duration: 0.5,
    onComplete: done,
    delay: el.dataset.index * 0.1
  })
}

const leave = (el, done) => {
  gsap.to(el, {
    opacity: 0,
    y: 20,
    duration: 0.5,
    onComplete: done
  })
}
</script>
