<template>
  <div class="watch-page">
    <div class="container">
      <div class="video-wrapper">
        <video v-if="episode" ref="video" :class="{ visible: videoPlayed }" controls autoplay :poster="episode.poster_url ? episode.poster_url : ''"></video>
        <div v-if="videoError && !videoPlayed" class="loading" style="color:#ff6b6b;">Không phát được video. Vui lòng thử lại hoặc chọn tập khác.</div>
        <div v-else-if="!episode" class="loading">Đang tải tập phim...</div>
      </div>
      <div class="episode-info-flex" v-if="episode">
        <div v-if="movie && (movie.poster || movie.poster_url)" class="info-poster">
          <img :src="movie.poster || movie.poster_url" :alt="movie.title" />
        </div>
        <div class="info-main">
          <div v-if="movie" class="movie-title">{{ movie.title }}</div>
          <h1>{{ episode.title }}</h1>
          <p class="desc">{{ episode.description }}</p>
        </div>
      </div>
      <div class="episode-list" v-if="episodes.length">
        <h2>Danh sách tập</h2>

        <div class="episode-tabs" role="tablist" aria-label="Episode groups">
          <button :class="['tab-btn', { active: selectedGroup==='all' }]" @click="selectedGroup='all'">Tất cả ({{ episodes.length }})</button>
          <button :class="['tab-btn', { active: selectedGroup==='vietsub' }]" @click="selectedGroup='vietsub'">Vietsub ({{ vietsubEpisodes.length }})</button>
          <button :class="['tab-btn', { active: selectedGroup==='thuyetminh' }]" @click="selectedGroup='thuyetminh'">Thuyết minh ({{ thuyetminhEpisodes.length }})</button>
          <button v-if="otherEpisodes.length" :class="['tab-btn', { active: selectedGroup==='other' }]" @click="selectedGroup='other'">Khác ({{ otherEpisodes.length }})</button>
        </div>

        <div class="episode-list-grid" aria-live="polite">
          <button v-for="ep in displayedEpisodes" :key="ep.id"
            :class="['episode-btn', { active: ep.id === episode.id }]"
            @click="goToEpisode(ep)">
            {{ ep.title || ('Tập ' + ep.id) }}
          </button>
        </div>
      </div>
      <!-- debug panel removed -->
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Hls from 'hls.js'
import authService from '../services/auth'

export default {
  name: 'Watch',
  props: ['id'],
  data() {
    return {
      episode: null,
      episodes: [],
      movie: null,
      videoError: false,
      videoPlayed: false
      , selectedGroup: 'all'
    }
  },
  async mounted() {
    // Check authentication before loading anything
    if (!authService.isAuthenticated()) {
      this.$router.push({
        name: 'Login',
        query: { redirect: this.$route.fullPath }
      })
      return
    }

    await this.loadEpisode()
    if (this.episode && this.episode.movie_id) {
      this.loadEpisodes(this.episode.movie_id)
      this.loadMovie(this.episode.movie_id)
    }
  },
  watch: {
    async id() {
      await this.loadEpisode()
      if (this.episode && this.episode.movie_id) {
        this.loadEpisodes(this.episode.movie_id)
        this.loadMovie(this.episode.movie_id)
      }
    }
  },
  computed: {
    filteredEpisodes() {
      // Nếu có nhiều tập tên 'Full', chỉ lấy 1 tập đầu tiên, các tập khác loại bỏ
      const seenFull = { full: false };
      return this.episodes.filter(ep => {
        if (ep.title && ep.title.trim().toLowerCase() === 'full') {
          if (seenFull.full) return false;
          seenFull.full = true;
          return true;
        }
        return true;
      });
    }
    ,
    // episodes grouped by type: vietsub / thuyetminh / other
    vietsubEpisodes() {
      return this.episodes.filter(ep => ep._isViet)
    },
    thuyetminhEpisodes() {
      return this.episodes.filter(ep => ep._isDub)
    },
    otherEpisodes() {
      return this.episodes.filter(ep => !this.vietsubEpisodes.includes(ep) && !this.thuyetminhEpisodes.includes(ep))
    },
    displayedEpisodes() {
      if (this.selectedGroup === 'vietsub') return this.vietsubEpisodes
      if (this.selectedGroup === 'thuyetminh') return this.thuyetminhEpisodes
      if (this.selectedGroup === 'other') return this.otherEpisodes
      return this.filteredEpisodes
    }
  },
  methods: {
    async loadEpisode() {
      this.episode = null
      this.videoError = false
      try {
        const apiUrl = import.meta.env.VITE_API_URL || import.meta.env.VUE_APP_API_BASE_URL || 'https://webxemphim-bxpf.onrender.com/api';
        const res = await axios.get(`${apiUrl}/episodes/${this.id}`)
        let data = res.data
        // Nếu video_url là link player phimapi.com, tự động lấy link gốc .m3u8
        if (typeof data.video_url === 'string' && data.video_url.includes('player.phimapi.com/player/?url=')) {
          const match = data.video_url.match(/url=([^&]+)/)
          if (match && match[1]) {
            data.video_url = decodeURIComponent(match[1])
          }
        }
        this.episode = data
        this.$nextTick(this.setupPlayer)
      } catch (e) {
        this.episode = null
      }
    },
    async loadEpisodes(movieId) {
      try {
        const apiUrl = import.meta.env.VITE_API_URL || import.meta.env.VUE_APP_API_BASE_URL || 'https://webxemphim-bxpf.onrender.com/api';
        const res = await axios.get(`${apiUrl}/movies/${movieId}/episodes`)
        this.episodes = res.data
        // normalize flags per episode for reliable grouping
        this.episodes.forEach(ep => {
          const t = (ep.title || '').toString().toLowerCase()
          const s = (ep.subtitle || '').toString().toLowerCase()
          const a = (ep.audio || '').toString().toLowerCase()
          const lang = (ep.lang || '').toString().toLowerCase()
          // detect tokens around '+' like "Vietsub + Lồng Tiếng"
          const plusParts = (ep.title || '').toString().toLowerCase().split(/\+|,|\/|-/).map(p => p.trim())
          const plusHasViet = plusParts.some(p => p.includes('viet') || p.includes('sub'))
          const plusHasDub = plusParts.some(p => p.includes('lồng') || p.includes('thuy') || p.includes('dub') || p.includes('vn'))
          const isViet = !!(
            plusHasViet ||
            lang.includes('viet') ||
            (lang === 'vi') ||
            (a.includes('vietsub')) ||
            (a.includes('sub') && !a.includes('dub')) ||
            t.includes('vietsub') ||
            s.includes('vietsub')
          )
          const isDub = !!(
            plusHasDub ||
            lang.includes('dub') ||
            lang === 'vn' ||
            a.includes('dub') ||
            a.includes('lồng') ||
            t.includes('thuy') ||
            t.includes('lồng') ||
            s.includes('thuy') ||
            s.includes('lồng')
          )
          ep._isViet = isViet
          ep._isDub = isDub
        })
        // debug: print episodes to console so backend data can be inspected
        console.log('loaded episodes for movie', movieId, this.episodes)
      } catch (e) {
        this.episodes = []
      }
    },
    async loadMovie(movieId) {
      try {
        const apiUrl = import.meta.env.VITE_API_URL || import.meta.env.VUE_APP_API_BASE_URL || 'https://webxemphim-bxpf.onrender.com/api';
        const res = await axios.get(`${apiUrl}/movies/${movieId}`)
        const data = res.data
        if (data.poster_url) {
          data.poster = data.poster_url.startsWith('http')
            ? data.poster_url
            : `https://img.phimapi.com/${data.poster_url.replace(/^\//, '')}`
        }
        this.movie = data
      } catch (e) {
        this.movie = null
      }
    },
    goToEpisode(ep) {
      // ep is the clicked episode object
        this.videoError = false // Reset lỗi khi chuyển tập
      // if user selected a group prefer an episode in that group (e.g. Vietsub)
      let target = ep
      if (this.selectedGroup === 'vietsub') {
        // if clicked ep isn't marked Vietsub, try find matching episode that is
        if (!ep._isViet) {
          const match = this.episodes.find(e => e._isViet && (e.title === ep.title || e.episode_number === ep.episode_number || e.part === ep.part))
          if (match) target = match
        }
      } else if (this.selectedGroup === 'thuyetminh') {
        if (!ep._isDub) {
          const match = this.episodes.find(e => e._isDub && (e.title === ep.title || e.episode_number === ep.episode_number || e.part === ep.part))
          if (match) target = match
        }
      }
      const id = target.id
      if (id !== this.episode?.id) {
        this.$router.push({ name: 'Watch', params: { id } })
      }
    },
    setupPlayer() {
      const video = this.$refs.video
      if (!video || !this.episode || !this.episode.video_url) return
      this.videoError = false
      this.videoPlayed = false
      video.onerror = () => {
        if (!this.videoPlayed) this.videoError = true
      }
      // Ẩn lỗi khi video thực sự phát được (canplay hoặc playing)
      video.oncanplay = () => {
        this.videoError = false
        this.videoPlayed = true
      }
      video.onplaying = () => {
        this.videoError = false
        this.videoPlayed = true
      }
      if (this.episode.video_url.endsWith('.m3u8') || this.episode.video_url.includes('.m3u8')) {
        if (Hls.isSupported()) {
          const hls = new Hls()
          hls.loadSource(this.episode.video_url)
          hls.attachMedia(video)
          hls.on(Hls.Events.ERROR, () => {
            if (!this.videoPlayed) this.videoError = true
          })
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
          video.src = this.episode.video_url
        }
      } else {
        video.src = this.episode.video_url
      }
    }
  }
}
</script>

<style scoped>
.watch-page {
  min-height: 100vh;
  background: linear-gradient(180deg,#071e26 0%, #0b1620 100%);
  padding: 3rem 0 4rem;
  display: flex;
  align-items: flex-start;
  color: #e6eef6;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.25rem;
}

.video-wrapper {
  width: 100%;
  max-width: 1200px;
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(2,6,12,0.7);
  margin: 0 auto 1.75rem;
  position: relative;
  padding: 0;
  aspect-ratio: 16/9;
  height: auto;
  min-height: 480px;
}

.video-wrapper video {
  position: relative;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: block;
  background: #000;
  border-radius: 12px;
  opacity: 0;
  transition: opacity 300ms ease;
}
.video-wrapper video.visible { opacity: 1; }

.loading {
  color: #ffd96a;
  text-align: center;
  padding: 2rem 0;
  font-size: 1.1rem;
}

.episode-info-flex {
  display: flex;
  gap: 1.5rem;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.info-poster {
  flex: 0 0 140px;
  width: 140px;
  border-radius: 12px;
  overflow: hidden;
  background: linear-gradient(180deg,#111,#0c0f12);
  box-shadow: 0 14px 34px rgba(2,6,12,0.7);
  border: 1px solid rgba(255,255,255,0.03);
}
.info-poster img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.info-main {
  flex: 1;
  min-width: 0;
}

.movie-title {
  color: #ffd96a;
  font-size: 1.25rem;
  font-weight: 800;
  margin-bottom: 0.35rem;
}
.info-main h1 {
  color: #fff;
  font-size: 1.6rem;
  margin: 0.2rem 0 0.6rem;
  font-weight: 700;
}
.desc {
  color: #cbd5f5;
  font-size: 1rem;
  line-height: 1.7;
  margin-bottom: 0.5rem;
  max-width: 720px;
}

.movie-actions {
  margin-top: 1.4rem;
  display: flex;
  gap: 1rem;
  align-items: center;
}
.btn-play {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(180deg,#ffd96a,#ffb84a);
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: #0b0b0b;
  box-shadow: 0 14px 36px rgba(2,6,12,0.6);
  cursor: pointer;
}
.btn-favorite {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.03);
  color: #ffd96a;
  padding: 0.5rem 0.9rem;
  border-radius: 999px;
  cursor: pointer;
}
.btn-favorite.active {
  background: #ffd96a;
  color: #0b0b0b;
}

.episode-list {
  margin-top: 2rem;
}
.episode-list h2 {
  color: #ffd96a;
  font-size: 1.15rem;
  margin-bottom: 1rem;
  font-weight: 800;
}
.episode-tabs { display:flex; gap:0.5rem; margin-bottom:0.85rem; flex-wrap:wrap; }
.tab-btn { background: rgba(255,255,255,0.02); color:#fff; border:1px solid rgba(255,255,255,0.03); padding:0.35rem 0.7rem; border-radius:8px; cursor:pointer; font-weight:700; }
.tab-btn.active, .tab-btn:hover { background:#ffd96a; color:#081018; transform:translateY(-2px); }
.episode-list-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}
.episode-btn {
  background: rgba(255,255,255,0.02);
  color: #fff;
  border: 1px solid rgba(255,255,255,0.03);
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  padding: 0.45rem 0.9rem;
  cursor: pointer;
  transition: all 0.18s ease;
}
.episode-btn.active, .episode-btn:hover {
  background: #ffd96a;
  color: #0b0b0b;
  transform: translateY(-3px);
}

@media (max-width: 900px) {
  .container { padding: 1rem; }
  .video-wrapper { padding-top: 56.25%; border-radius: 12px; }
  .episode-info-flex { flex-direction: column; gap: 1rem; }
  .info-poster { width: 110px; flex: 0 0 110px; }
  .movie-title { font-size: 1.05rem; }
  .info-main h1 { font-size: 1.1rem; }
  .desc { font-size: 0.95rem; }
}

</style>

  .video-wrapper { padding-top: 56.25%; border-radius: 12px; }
  .episode-info-flex { flex-direction: column; gap: 1rem; }
  .info-poster { width: 110px; flex: 0 0 110px; }
  .movie-title { font-size: 1.05rem; }
  .info-main h1 { font-size: 1.1rem; }
  .desc { font-size: 0.95rem; }
}

</style>
