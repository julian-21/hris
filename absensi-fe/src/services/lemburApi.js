// @/services/lemburApi.js
import api from './api' // atau axios instance Anda

export const lemburApi = {
  // Lembur CRUD
  getAll: (params) => api.get('/lemburs', { params }),
  getById: (id) => api.get(`/lemburs/${id}`),
  create: (data) => api.post('/lemburs', data),
  update: (id, data) => api.put(`/lemburs/${id}`, data),
  delete: (id) => api.delete(`/lemburs/${id}`),
  
  // Lembur Approval
  approve: (id, data) => api.post(`/lemburs/${id}/approve`, data),
  reject: (id, data) => api.post(`/lemburs/${id}/reject`, data),
  finalApprove: (id, data) => api.post(`/lemburs/${id}/final-approve`, data),
  finalReject: (id, data) => api.post(`/lemburs/${id}/final-reject`, data),
  
  // Statistics
  getStatistics: (params) => api.get('/lemburs/statistics', { params })
}


export default {
  lembur: lemburApi
}