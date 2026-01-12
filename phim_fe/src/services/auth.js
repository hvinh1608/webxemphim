import axios from 'axios';

// API base URL - production backend
const API_BASE_URL = 'https://webxemphim-bxpf.onrender.com/api';

class AuthService {
  async login(credentials) {
    try {
      const response = await axios.post(`${API_BASE_URL}/login`, credentials);
      if (response.data.success) {
        console.log('🔐 Login successful, saving to localStorage:', response.data);

        // Lưu token vào localStorage
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));

        // Set Authorization header cho các request sau
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

        // Emit event to notify components
        window.dispatchEvent(new CustomEvent('auth-changed', { detail: { action: 'login' } }));

        console.log('✅ Auth data saved, event emitted');
      }
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  async register(userData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/register`, userData);
      // KHÔNG tự động lưu token khi đăng ký - chỉ lưu khi đăng nhập thực sự
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  async forgotPassword(data) {
    try {
      const response = await axios.post(`${API_BASE_URL}/forgot-password`, data);
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  async resetPassword(data) {
    try {
      const response = await axios.post(`${API_BASE_URL}/reset-password`, data);
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  async logout() {
    try {
      const response = await axios.post(`${API_BASE_URL}/logout`);
      // Xóa token và user khỏi localStorage
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      delete axios.defaults.headers.common['Authorization'];

      // Emit event to notify components
      window.dispatchEvent(new CustomEvent('auth-changed', { detail: { action: 'logout' } }));

      return response.data;
    } catch (error) {
      // Vẫn xóa local data ngay cả khi API call fail
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      delete axios.defaults.headers.common['Authorization'];

      // Emit event to notify components
      window.dispatchEvent(new CustomEvent('auth-changed', { detail: { action: 'logout' } }));

      throw error.response?.data || error;
    }
  }

  async getCurrentUser() {
    try {
      const response = await axios.get(`${API_BASE_URL}/user`);
      return response.data;
    } catch (error) {
      throw error.response?.data || error;
    }
  }

  isAuthenticated() {
    const token = localStorage.getItem('token');
    console.log('🔍 isAuthenticated check:', !!token, 'token:', token);
    return !!token;
  }

  getToken() {
    const token = localStorage.getItem('token');
    console.log('🔍 getToken:', token ? token.substring(0, 20) + '...' : null);
    return token;
  }

  getUser() {
    const userStr = localStorage.getItem('user');
    const user = userStr ? JSON.parse(userStr) : null;
    console.log('🔍 getUser:', user);
    return user;
  }

  // Khởi tạo khi app load
  initializeAuth() {
    const token = this.getToken();
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }
  }

  async resendVerification() {
    const token = this.getToken();
    return axios.post(`${API_BASE_URL}/resend-verification`, {}, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  }
}

const authService = new AuthService();
export default authService;
