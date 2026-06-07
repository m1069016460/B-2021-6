import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

// 消息去重
const recentMessages = new Set()

const showError = (message) => {
  if (recentMessages.has(message)) return
  recentMessages.add(message)
  setTimeout(() => recentMessages.delete(message), 2000)
  ElMessage.error({ message, grouping: true })
}

const showSuccess = (message) => {
  ElMessage.success({ message, grouping: true })
}

const request = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// 请求拦截器
request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  (response) => {
    const res = response.data
    
    // 业务错误处理
    if (res.code !== 200) {
      showError(res.message || '操作失败')
      const error = new Error(res.message)
      error._isBusinessError = true
      return Promise.reject(error)
    }
    
    return res
  },
  (error) => {
    // 已处理的业务错误
    if (error._isBusinessError) {
      return Promise.reject(error)
    }
    
    // HTTP错误处理
    if (error.response) {
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          // 如果有API返回的错误消息，显示该消息（如密码错误）
          // 否则显示登录过期并跳转登录页
          if (data?.message) {
            showError(data.message)
          } else {
            showError('登录已过期，请重新登录')
          }
          // 只有当不在登录页时才跳转
          if (!window.location.pathname.includes('/login')) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            router.push('/login')
          }
          break
        case 403:
          showError('没有权限访问')
          break
        case 404:
          showError(data?.message || '请求的资源不存在')
          break
        case 500:
          showError(data?.message || '服务器内部错误')
          break
        default:
          showError(data?.message || `请求失败 (${status})`)
      }
    } else if (error.code === 'ECONNABORTED') {
      showError('请求超时，请重试')
    } else {
      showError('网络错误，请检查网络连接')
    }
    
    return Promise.reject(error)
  }
)

export { request, showSuccess, showError }
