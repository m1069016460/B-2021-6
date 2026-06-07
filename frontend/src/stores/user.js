import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import router from '@/router'

export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  
  const isLoggedIn = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  
  function setToken(newToken) {
    token.value = newToken
    localStorage.setItem('token', newToken)
  }
  
  function setUser(newUser) {
    user.value = newUser
    localStorage.setItem('user', JSON.stringify(newUser))
  }
  
  async function login(credentials) {
    const res = await authApi.login(credentials)
    setToken(res.data.token)
    setUser(res.data.user)
    return res
  }
  
  async function register(data) {
    return await authApi.register(data)
  }
  
  async function fetchProfile() {
    const res = await authApi.getProfile()
    setUser(res.data)
    return res
  }
  
  async function updateProfile(data) {
    const res = await authApi.updateProfile(data)
    if (user.value) {
      setUser({ ...user.value, ...data })
    }
    return res
  }
  
  async function changePassword(data) {
    return await authApi.changePassword(data)
  }
  
  function logout() {
    token.value = ''
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/login')
  }
  
  async function checkAuth() {
    if (token.value) {
      try {
        await fetchProfile()
      } catch (error) {
        logout()
      }
    }
  }
  
  return {
    token,
    user,
    isLoggedIn,
    isAdmin,
    login,
    register,
    logout,
    fetchProfile,
    updateProfile,
    changePassword,
    checkAuth
  }
})
