import { request } from '@/utils/request'

export const studentApi = {
  getList(params) {
    return request.get('/students', { params })
  },
  
  getById(id) {
    return request.get(`/students/${id}`)
  },
  
  create(data) {
    return request.post('/students', data)
  },
  
  update(id, data) {
    return request.put(`/students/${id}`, data)
  },
  
  delete(id) {
    return request.delete(`/students/${id}`)
  },
  
  import(formData) {
    return request.post('/students/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  pasteImport(data) {
    return request.post('/students/paste-import', data)
  },
  
  export(params) {
    return `/api/students/export?token=${localStorage.getItem('token')}${params?.classId ? `&classId=${params.classId}` : ''}`
  },
  
  getTemplate() {
    return `/api/students/template?token=${localStorage.getItem('token')}`
  }
}

export const classApi = {
  getList(params) {
    return request.get('/classes', { params })
  },
  
  getAll() {
    return request.get('/classes/all')
  },
  
  getById(id) {
    return request.get(`/classes/${id}`)
  },
  
  create(data) {
    return request.post('/classes', data)
  },
  
  update(id, data) {
    return request.put(`/classes/${id}`, data)
  },
  
  delete(id) {
    return request.delete(`/classes/${id}`)
  },
  
  getStudents(id) {
    return request.get(`/classes/${id}/students`)
  }
}

export const courseApi = {
  getList(params) {
    return request.get('/courses', { params })
  },
  
  getAll() {
    return request.get('/courses/all')
  },
  
  getById(id) {
    return request.get(`/courses/${id}`)
  },
  
  create(data) {
    return request.post('/courses', data)
  },
  
  update(id, data) {
    return request.put(`/courses/${id}`, data)
  },
  
  delete(id) {
    return request.delete(`/courses/${id}`)
  }
}

export const gradeApi = {
  getList(params) {
    return request.get('/grades', { params })
  },
  
  getById(id) {
    return request.get(`/grades/${id}`)
  },
  
  create(data) {
    return request.post('/grades', data)
  },
  
  update(id, data) {
    return request.put(`/grades/${id}`, data)
  },
  
  delete(id) {
    return request.delete(`/grades/${id}`)
  },
  
  getStudentGrades(studentId, params) {
    return request.get(`/grades/student/${studentId}`, { params })
  },
  
  import(formData) {
    return request.post('/grades/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  pasteImport(data) {
    return request.post('/grades/paste-import', data)
  },
  
  export(params) {
    let url = `/api/grades/export?token=${localStorage.getItem('token')}`
    if (params?.courseId) url += `&courseId=${params.courseId}`
    if (params?.semester) url += `&semester=${params.semester}`
    return url
  }
}

export const gradeChangeRequestApi = {
  getList(params) {
    return request.get('/grade-change-requests', { params })
  },

  getById(id) {
    return request.get(`/grade-change-requests/${id}`)
  },

  submit(data) {
    return request.post('/grade-change-requests', data)
  },

  approve(id) {
    return request.put(`/grade-change-requests/${id}/approve`)
  },

  reject(id, data) {
    return request.put(`/grade-change-requests/${id}/reject`, data)
  },

  resubmit(id, data) {
    return request.put(`/grade-change-requests/${id}/resubmit`, data)
  }
}

export const statisticsApi = {
  getOverview() {
    return request.get('/statistics/overview')
  },
  
  getClassStats(classId, params) {
    return request.get(`/statistics/class/${classId}`, { params })
  },
  
  getCourseStats(courseId, params) {
    return request.get(`/statistics/course/${courseId}`, { params })
  },
  
  getStudentStats(studentId) {
    return request.get(`/statistics/student/${studentId}`)
  },
  
  getRanking(params) {
    return request.get('/statistics/ranking', { params })
  },
  
  getDistribution(params) {
    return request.get('/statistics/distribution', { params })
  },
  
  getTrend(params) {
    return request.get('/statistics/trend', { params })
  }
}

export const userApi = {
  getList(params) {
    return request.get('/users', { params })
  },
  
  getById(id) {
    return request.get(`/users/${id}`)
  },
  
  create(data) {
    return request.post('/users', data)
  },
  
  update(id, data) {
    return request.put(`/users/${id}`, data)
  },
  
  delete(id) {
    return request.delete(`/users/${id}`)
  }
}
