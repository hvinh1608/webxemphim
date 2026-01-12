<template>
  <div class="auth-page">
    <div class="container">
      <div class="auth-container">
        <div class="auth-header">
          <h1>Đặt lại mật khẩu</h1>
          <p>Nhập mật khẩu mới cho tài khoản của bạn</p>
        </div>

        <form @submit.prevent="handleSubmit" class="auth-form">
          <div class="form-group">
            <label for="password">Mật khẩu mới</label>
            <div class="password-input-container">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                required
                minlength="6"
                class="form-input"
                :disabled="loading"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="password-toggle"
                :class="{ 'active': showPassword }"
                :disabled="loading"
              >
                <svg v-if="showPassword" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Xác nhận mật khẩu</label>
            <div class="password-input-container">
              <input
                id="confirmPassword"
                v-model="form.confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Nhập lại mật khẩu mới"
                required
                class="form-input"
                :disabled="loading"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="password-toggle"
                :class="{ 'active': showConfirmPassword }"
                :disabled="loading"
              >
                <svg v-if="showConfirmPassword" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <div v-if="success" class="success-message">
            {{ success }}
          </div>

          <button type="submit" class="auth-btn" :disabled="loading">
            <span v-if="loading">Đang đặt lại mật khẩu...</span>
            <span v-else>Đặt lại mật khẩu</span>
          </button>
        </form>

        <div class="auth-footer">
          <p>
            <router-link to="/login" class="auth-link">Quay lại đăng nhập</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import authService from '../services/auth.js'
import axios from 'axios'

export default {
  name: 'ResetPassword',
  data() {
    return {
      form: {
        password: '',
        confirmPassword: ''
      },
      showPassword: false,
      showConfirmPassword: false,
      token: '',
      loading: false,
      error: null,
      success: null
    }
  },
  created() {
    // Get token from URL query parameter
    this.token = this.$route.query.token || ''

    if (!this.token) {
      this.error = 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
    }
  },
  methods: {
    validateForm() {
      if (this.form.password.length < 6) {
        this.error = 'Mật khẩu phải có ít nhất 6 ký tự.'
        return false
      }

      if (this.form.password !== this.form.confirmPassword) {
        this.error = 'Mật khẩu xác nhận không khớp.'
        return false
      }

      return true
    },

    async handleSubmit() {
      if (this.loading || !this.validateForm()) return

      this.loading = true
      this.error = null
      this.success = null

      try {
        const apiUrl = import.meta.env.VITE_API_URL || import.meta.env.VUE_APP_API_BASE_URL || 'https://webxemphim-bxpf.onrender.com/api';
        const response = await axios.post(`${apiUrl}/reset-password`, {
          token: this.token,
          password: this.form.password,
          password_confirmation: this.form.confirmPassword
        })

        if (response.data.success) {
          this.success = 'Mật khẩu đã được đặt lại thành công! Bạn có thể đăng nhập với mật khẩu mới.'
          this.form.password = ''
          this.form.confirmPassword = ''

          // Redirect to login after 3 seconds
          setTimeout(() => {
            this.$router.push('/login')
          }, 3000)
        }
      } catch (error) {
        console.error('Reset password error:', error)

        if (error.errors) {
          const firstError = Object.values(error.errors)[0]
          const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError
          this.error = errorMessage
        } else if (error.message) {
          this.error = error.message
        } else {
          this.error = 'Có lỗi xảy ra, vui lòng thử lại'
        }

        this.$toast.error(this.error, {
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
.auth-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #071e26 0%, #1a2224 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.auth-container {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 3rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 100%;
}

.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-header h1 {
  color: #fff;
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.auth-header p {
  color: #ccc;
  font-size: 1rem;
  margin: 0;
}

.auth-form {
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  color: #fff;
  font-size: 1rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.form-input {
  width: 100%;
  padding: 1rem 1.25rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #ffd96a;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(255, 217, 106, 0.1);
}

.form-input::placeholder {
  color: #999;
}

.form-input:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.password-input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  color: #ccc;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.password-toggle:hover:not(:disabled) {
  color: #ffd96a;
  background: rgba(255, 217, 106, 0.1);
}

.password-toggle.active {
  color: #ffd96a;
}

.password-toggle:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.auth-btn {
  width: 100%;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #ffd96a 0%, #ffdd8a 100%);
  border: none;
  border-radius: 8px;
  color: #000;
  font-size: 1.2rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.auth-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #ffdd8a 0%, #ffd96a 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 217, 106, 0.3);
}

.auth-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.error-message {
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.3);
  color: #ff6b6b;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  text-align: center;
}

.success-message {
  background: rgba(40, 167, 69, 0.1);
  border: 1px solid rgba(40, 167, 69, 0.3);
  color: #28a745;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  text-align: center;
  line-height: 1.5;
}

.auth-footer {
  text-align: center;
}

.auth-footer p {
  color: #ccc;
  font-size: 1rem;
  margin: 0;
}

.auth-link {
  color: #ffd96a;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s ease;
}

.auth-link:hover {
  color: #ffdd8a;
}

@media (max-width: 768px) {
  .auth-container {
    padding: 2rem 1.5rem;
  }

  .auth-header h1 {
    font-size: 1.75rem;
  }

  .auth-header p {
    font-size: 1rem;
  }

  .form-input {
    font-size: 1rem;
    padding: 0.875rem 3rem 0.875rem 1rem;
  }

  .password-toggle {
    right: 0.75rem;
  }

  .auth-btn {
    font-size: 1.1rem;
    padding: 0.875rem 1rem;
  }

  .auth-page {
    padding: 0.5rem;
  }
}
</style>
