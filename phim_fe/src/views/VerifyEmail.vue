<template>
  <div class="verify-page">
    <div class="container">
      <div class="verify-container">
        <div class="verify-header">
          <h1>Xác nhận email</h1>
          <p>Vui lòng kiểm tra email của bạn để xác nhận tài khoản</p>
        </div>

        <div class="verify-content">
          <div class="verify-icon">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M22 10.5V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v4.5"/>
              <path d="M22 10.5V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v4.5"/>
              <path d="m22 10.5-10 4.5L2 10.5"/>
              <path d="M6 14v4a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-4"/>
            </svg>
          </div>

          <div class="verify-message">
            <h2>Email xác nhận đã được gửi!</h2>
            <p>
              Chúng tôi đã gửi email xác nhận đến địa chỉ:
              <strong>{{ email }}</strong>
            </p>
            <p>
              Vui lòng kiểm tra hộp thư đến và click vào link xác nhận để hoàn tất đăng ký tài khoản.
            </p>
          </div>

          <div class="verify-actions">
            <button @click="resendEmail" :disabled="loading" class="resend-btn">
              <span v-if="loading">Đang gửi...</span>
              <span v-else>Gửi lại email xác nhận</span>
            </button>

            <router-link to="/login" class="back-link">
              Quay lại đăng nhập
            </router-link>
          </div>

          <div class="verify-tips">
            <h3>Mẹo:</h3>
            <ul>
              <li>Kiểm tra hộp thư Spam/Junk nếu không thấy email</li>
              <li>Link xác nhận sẽ hết hạn sau 24 giờ</li>
              <li>Đảm bảo email được nhập chính xác</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import authService from '../services/auth.js'
import axios from 'axios'

export default {
  name: 'VerifyEmail',
  data() {
    return {
      email: '',
      loading: false
    }
  },
  created() {
    // Get email from route params or query
    this.email = this.$route.query.email || this.$route.params.email || ''
  },
  methods: {
    async resendEmail() {
      if (this.loading) return

      this.loading = true
      try {
        // Gọi API resend-verification public, truyền email lên
        const apiUrl = import.meta.env.VITE_API_URL || import.meta.env.VUE_APP_API_BASE_URL || 'https://webxemphim-bxpf.onrender.com/api';
        await axios.post(`${apiUrl}/resend-verification`, { email: this.email })
        this.$toast.success('Email xác nhận đã được gửi lại!', {
          duration: 4000,
          position: 'top-right'
        })
      } catch (error) {
        console.error('Resend error:', error)
        this.$toast.error('Có lỗi xảy ra, vui lòng thử lại', {
          duration: 5000,
          position: 'top-right'
        })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #071e26 0%, #1a2224 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.verify-container {
  max-width: 500px;
  width: 100%;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 3rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  text-align: center;
}

.verify-header {
  margin-bottom: 2rem;
}

.verify-header h1 {
  color: #fff;
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.verify-header p {
  color: #ccc;
  font-size: 1rem;
  margin: 0;
}

.verify-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

.verify-icon {
  color: #ffd96a;
  opacity: 0.8;
}

.verify-message h2 {
  color: #fff;
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.verify-message p {
  color: #ccc;
  font-size: 0.95rem;
  line-height: 1.6;
  margin: 0 0 1rem 0;
}

.verify-message strong {
  color: #ffd96a;
}

.verify-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  width: 100%;
  max-width: 300px;
}

.resend-btn {
  width: 100%;
  padding: 0.875rem 1rem;
  background: linear-gradient(135deg, #ffd96a 0%, #ffdd8a 100%);
  border: none;
  border-radius: 8px;
  color: #000;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.resend-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #ffdd8a 0%, #ffd96a 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 217, 106, 0.3);
}

.resend-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.back-link {
  color: #ffd96a;
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;
  transition: color 0.3s ease;
  text-align: center;
}

.back-link:hover {
  color: #ffdd8a;
}

.verify-tips {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  padding: 1.5rem;
  width: 100%;
}

.verify-tips h3 {
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.verify-tips ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.verify-tips li {
  color: #ccc;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  padding-left: 1rem;
  position: relative;
}

.verify-tips li:before {
  content: "•";
  color: #ffd96a;
  position: absolute;
  left: 0;
}

@media (max-width: 768px) {
  .verify-container {
    padding: 2rem 1.5rem;
  }

  .verify-header h1 {
    font-size: 1.75rem;
  }

  .verify-message h2 {
    font-size: 1.25rem;
  }

  .verify-page {
    padding: 1rem;
  }
}
</style>
