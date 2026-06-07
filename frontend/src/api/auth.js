import { request } from '@/utils/request'

export const authApi = {
  login(data) {
    return request.post('/auth/login', data)
  },
  
  register(data) {
    return request.post('/auth/register', data)
  },
  
  getProfile() {
    return request.get('/auth/profile')
  },
  
  updateProfile(data) {
    return request.put('/auth/profile', data)
  },
  
  changePassword(data) {
    return request.put('/auth/password', data)
  }
}
