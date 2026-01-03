<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  // For YouTube videos
  youtubeUrl?: string
  // For Vimeo videos
  vimeoUrl?: string
  // For direct video files
  videoSrc?: string
  // Player options
  autoplay?: boolean
  muted?: boolean
  loop?: boolean
  poster?: string
  // Layout
  aspectRatio?: string
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: false,
  muted: false,
  loop: false,
  aspectRatio: '16/9',
})

const emit = defineEmits<{
  ready: []
  play: []
  pause: []
  ended: []
  error: [error: any]
}>()

// Extract YouTube video ID from URL
const getYoutubeVideoId = (url: string): string | null => {
  if (!url) return null

  // Already just a video ID (11 characters)
  if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
    return url
  }

  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/,
    /youtu\.be\/([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/,
  ]

  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) {
      return match[1]
    }
  }

  return null
}

// Extract Vimeo video ID from URL
const getVimeoVideoId = (url: string): string | null => {
  if (!url) return null

  const patterns = [/vimeo\.com\/(\d+)/, /player\.vimeo\.com\/video\/(\d+)/]

  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) {
      return match[1]
    }
  }

  return null
}

const youtubeVideoId = computed(() =>
  props.youtubeUrl ? getYoutubeVideoId(props.youtubeUrl) : null
)
const vimeoVideoId = computed(() => (props.vimeoUrl ? getVimeoVideoId(props.vimeoUrl) : null))

const providerType = computed<'youtube' | 'vimeo' | 'html5' | null>(() => {
  if (youtubeVideoId.value) return 'youtube'
  if (vimeoVideoId.value) return 'vimeo'
  if (props.videoSrc) return 'html5'
  return null
})

// Generate embed URL
const youtubeEmbedUrl = computed(() => {
  if (!youtubeVideoId.value) return null
  const params = new URLSearchParams({
    rel: '0',
    modestbranding: '1',
    playsinline: '1',
  })
  if (props.autoplay) params.set('autoplay', '1')
  if (props.muted) params.set('mute', '1')
  if (props.loop) {
    params.set('loop', '1')
    params.set('playlist', youtubeVideoId.value)
  }
  return `https://www.youtube-nocookie.com/embed/${youtubeVideoId.value}?${params.toString()}`
})

const vimeoEmbedUrl = computed(() => {
  if (!vimeoVideoId.value) return null
  const params = new URLSearchParams({
    byline: '0',
    portrait: '0',
    title: '0',
  })
  if (props.autoplay) params.set('autoplay', '1')
  if (props.muted) params.set('muted', '1')
  if (props.loop) params.set('loop', '1')
  return `https://player.vimeo.com/video/${vimeoVideoId.value}?${params.toString()}`
})

// Poster URL
const posterUrl = computed(() => {
  if (props.poster) return props.poster
  if (youtubeVideoId.value) {
    return `https://img.youtube.com/vi/${youtubeVideoId.value}/maxresdefault.jpg`
  }
  return undefined
})

const iframeLoaded = ref(false)
const handleIframeLoad = () => {
  iframeLoaded.value = true
  emit('ready')
}
</script>

<template>
  <ClientOnly>
    <div
      class="video-player-wrapper w-full relative bg-black rounded-lg overflow-hidden"
      :style="{ aspectRatio: aspectRatio }"
    >
      <!-- YouTube Player -->
      <template v-if="providerType === 'youtube' && youtubeEmbedUrl">
        <div v-if="!iframeLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-900">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-2 bg-gray-800 rounded-full flex items-center justify-center animate-pulse">
              <Icon icon="fluent:play-24-filled" class="w-8 h-8 text-gray-600" />
            </div>
            <span class="text-gray-500 text-sm">กำลังโหลดวิดีโอ...</span>
          </div>
        </div>
        <iframe
          :src="youtubeEmbedUrl"
          class="absolute inset-0 w-full h-full"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen
          @load="handleIframeLoad"
        />
      </template>

      <!-- Vimeo Player -->
      <template v-else-if="providerType === 'vimeo' && vimeoEmbedUrl">
        <div v-if="!iframeLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-900">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-2 bg-gray-800 rounded-full flex items-center justify-center animate-pulse">
              <Icon icon="fluent:play-24-filled" class="w-8 h-8 text-gray-600" />
            </div>
            <span class="text-gray-500 text-sm">กำลังโหลดวิดีโอ...</span>
          </div>
        </div>
        <iframe
          :src="vimeoEmbedUrl"
          class="absolute inset-0 w-full h-full"
          frameborder="0"
          allow="autoplay; fullscreen; picture-in-picture"
          allowfullscreen
          @load="handleIframeLoad"
        />
      </template>

      <!-- HTML5 Video Player -->
      <video
        v-else-if="providerType === 'html5'"
        class="w-full h-full object-contain"
        :src="videoSrc"
        :poster="posterUrl"
        :autoplay="autoplay"
        :muted="muted"
        :loop="loop"
        controls
        playsinline
        @canplay="emit('ready')"
        @play="emit('play')"
        @pause="emit('pause')"
        @ended="emit('ended')"
      />

      <!-- No video source -->
      <div
        v-else
        class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800"
      >
        <span class="text-gray-500 dark:text-gray-400">ไม่มี video</span>
      </div>
    </div>

    <template #fallback>
      <!-- Loading placeholder -->
      <div
        class="w-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse"
        :style="{ aspectRatio: aspectRatio }"
      >
        <div class="text-center">
          <div
            class="w-16 h-16 mx-auto mb-2 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center"
          >
            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z" />
            </svg>
          </div>
          <span class="text-gray-500 dark:text-gray-400 text-sm">กำลังโหลดวิดีโอ...</span>
        </div>
      </div>
    </template>
  </ClientOnly>
</template>

<style scoped>
.video-player-wrapper {
  min-height: 200px;
}
</style>
