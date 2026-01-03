<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'

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
  controls?: boolean
  poster?: string
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: false,
  muted: false,
  loop: false,
  controls: true,
})

const emit = defineEmits<{
  ready: [player: any]
  play: []
  pause: []
  ended: []
  error: [error: any]
}>()

const playerRef = ref<HTMLElement | null>(null)
const isReady = ref(false)
let player: any = null
let Plyr: any = null

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

const providerType = computed(() => {
  if (youtubeVideoId.value) return 'youtube'
  if (vimeoVideoId.value) return 'vimeo'
  if (props.videoSrc) return 'html5'
  return null
})

const embedId = computed(() => {
  if (youtubeVideoId.value) return youtubeVideoId.value
  if (vimeoVideoId.value) return vimeoVideoId.value
  return null
})

const initPlayer = async () => {
  if (!playerRef.value || !providerType.value || !import.meta.client) return

  try {
    // Dynamic import Plyr only on client-side
    if (!Plyr) {
      const plyrModule = await import('plyr')
      Plyr = plyrModule.default
      // Import CSS
      await import('plyr/dist/plyr.css')
    }

    const options = {
      autoplay: props.autoplay,
      muted: props.muted,
      loop: { active: props.loop },
      controls: props.controls
        ? [
            'play-large',
            'play',
            'progress',
            'current-time',
            'mute',
            'volume',
            'captions',
            'settings',
            'pip',
            'airplay',
            'fullscreen',
          ]
        : [],
      youtube: {
        noCookie: true,
        rel: 0,
        showinfo: 0,
        iv_load_policy: 3,
        modestbranding: 1,
      },
      vimeo: {
        byline: false,
        portrait: false,
        title: false,
        transparent: false,
      },
    }

    player = new Plyr(playerRef.value, options)

    // Event listeners
    player.on('ready', () => {
      isReady.value = true
      emit('ready', player!)
    })
    player.on('play', () => emit('play'))
    player.on('pause', () => emit('pause'))
    player.on('ended', () => emit('ended'))
    player.on('error', (e: any) => emit('error', e))
  } catch (error) {
    console.error('Failed to initialize Plyr:', error)
    emit('error', error)
  }
}

onMounted(() => {
  // Small delay to ensure DOM is ready
  setTimeout(initPlayer, 200)
})

onUnmounted(() => {
  if (player) {
    player.destroy()
    player = null
  }
})

// Watch for source changes
watch([() => props.youtubeUrl, () => props.vimeoUrl, () => props.videoSrc], () => {
  if (player) {
    player.destroy()
    player = null
  }
  setTimeout(initPlayer, 200)
})
</script>

<template>
  <ClientOnly>
    <div class="plyr-wrapper w-full h-full">
      <!-- YouTube Player -->
      <div
        v-if="providerType === 'youtube' && embedId"
        ref="playerRef"
        class="plyr__video-embed w-full h-full"
        data-plyr-provider="youtube"
        :data-plyr-embed-id="embedId"
      ></div>

      <!-- Vimeo Player -->
      <div
        v-else-if="providerType === 'vimeo' && embedId"
        ref="playerRef"
        class="plyr__video-embed w-full h-full"
        data-plyr-provider="vimeo"
        :data-plyr-embed-id="embedId"
      ></div>

      <!-- HTML5 Video Player -->
      <video
        v-else-if="providerType === 'html5'"
        ref="playerRef"
        class="w-full h-full"
        playsinline
        :poster="poster"
      >
        <source :src="videoSrc" />
      </video>

      <!-- No video source -->
      <div
        v-else
        class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg"
      >
        <span class="text-gray-500 dark:text-gray-400">ไม่มี video</span>
      </div>
    </div>

    <template #fallback>
      <!-- Loading placeholder -->
      <div
        class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse"
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
.plyr-wrapper {
  --plyr-color-main: #3b82f6;
}

.plyr-wrapper :deep(.plyr) {
  width: 100%;
  height: 100%;
}

.plyr-wrapper :deep(.plyr__video-embed) {
  height: 100%;
  padding-bottom: 0 !important;
}

.plyr-wrapper :deep(.plyr__video-embed iframe) {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}
</style>
