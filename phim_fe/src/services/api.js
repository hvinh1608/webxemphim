import axios from 'axios'

// Create axios instance with base configuration
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || import.meta.env.VUE_APP_API_BASE_URL || 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor for adding auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor for handling errors
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized access
      localStorage.removeItem('auth_token')
      // Redirect to login if needed
    }
    return Promise.reject(error)
  }
)

// API endpoints
export const movieAPI = {
  // Get all movies with pagination and filters
  getMovies: (params = {}) => api.get('/movies', { params }),

  // Get movie by ID
  getMovie: (id) => api.get(`/movies/${id}`),

  // Get featured movies
  getFeaturedMovies: () => api.get('/movies/featured'),

  // Get latest movies
  getLatestMovies: () => api.get('/movies/latest'),

  // Get movies by category
  getMoviesByCategory: (slug, params = {}) => api.get(`/categories/${slug}/movies`, { params }),

  // Search movies
  searchMovies: (query, params = {}) => api.get('/search', { params: { q: query, ...params } }),

  // Get movie categories
  getCategories: () => api.get('/categories'),

  // Get related movies
  getRelatedMovies: (id, params = {}) => api.get(`/movies/${id}/related`, { params }),
}

export const userAPI = {
  // User authentication
  login: (credentials) => api.post('/auth/login', credentials),
  register: (userData) => api.post('/auth/register', userData),
  logout: () => api.post('/auth/logout'),
  getProfile: () => api.get('/auth/profile'),

  // User favorites
  getFavorites: () => api.get('/user/favorites'),
  addToFavorites: (movieId) => api.post('/user/favorites', { movie_id: movieId }),
  removeFromFavorites: (movieId) => api.delete(`/user/favorites/${movieId}`),

  // User watchlist
  getWatchlist: () => api.get('/user/watchlist'),
  addToWatchlist: (movieId) => api.post('/user/watchlist', { movie_id: movieId }),
  removeFromWatchlist: (movieId) => api.delete(`/user/watchlist/${movieId}`),
}

export default api
