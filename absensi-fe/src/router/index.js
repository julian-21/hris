import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../pages/auth/Login.vue'),
    meta: { guest: true, title: 'Login' }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../pages/auth/Register.vue'),
    meta: { guest: true, title: 'Register' }
  },
  {
    path: '/',
    component: () => import('../layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard'
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../pages/admin/Dashboard.vue'),
        meta: { requiresAuth: true, title: 'Dashboard' }
      },
      {
        path: 'attendance',
        name: 'Attendance',
        component: () => import('../pages/admin/Attendance.vue'),
        meta: { requiresAuth: true, title: 'Absensi', permission: 'view attendance' }
      },
      {
        path: 'kantors',
        name: 'Kantors',
        component: () => import('../pages/admin/Kantors.vue'),
        meta: { 
          title: 'Manajemen Kantor',
          roles: ['Admin', 'HR']
        }
      },
      {
        path: 'leave',
        name: 'Leave',
        component: () => import('../pages/admin/Leave.vue'),
        meta: { requiresAuth: true, title: 'Cuti', permission: 'view leave' }
      },
      {
        path: 'overtime',
        name: 'Overtime',
        component: () => import('../pages/admin/Lembur.vue'),
        meta: { requiresAuth: true, title: 'Lembur', permission: 'view overtime' }
      },
      {
        path: 'claim-lembur',
        name: 'ClaimLembur',
        component: () => import('../pages/admin/LemburClaimIndex.vue'),
        meta: { requiresAuth: true, title: 'Claim Lembur', permission: 'view overtime' }
      },
      {
        path: 'employee',
        name: 'Employee',
        component: () => import('../pages/admin/EmployeeManagement.vue'),
        meta: { requiresAuth: true, title: 'Karyawan', permission: 'view employee' }
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('../pages/admin/Attendancereport.vue'),
        meta: { 
          requiresAuth: true, 
          roles: ['Admin', 'HR', 'Direktur'],
          title: 'Laporan Kehadiran', 
          permission: 'view reports' }
      },
      // {
      //   path: 'roles',
      //   name: 'Roles',
      //   component: () => import('../pages/admin/Roles.vue'),
      //   meta: { requiresAuth: true, title: 'Roles & Permissions', roles: ['Admin', 'Direktur'] }
      // }
    ]
  },
  // {
  //   path: '/approval',
  //   name: 'Approval',
  //   component: () => import('../views/Approval.vue'),
  //   meta: { 
  //     requiresAuth: true,
  //     requiresApproval: true // Custom check untuk canApprove
  //   }
  // },
  // {
  //   path: '/admin',
  //   name: 'Admin',
  //   component: () => import('../views/Admin.vue'),
  //   meta: { 
  //     requiresAuth: true,
  //     roles: ['Admin'] // Hanya role Admin
  //   }
  // },
  {
    path: '/',
    redirect: '/dashboard'
  }
  // {
  //   path: '/:pathMatch(.*)*',
  //   name: 'NotFound',
  //   component: () => import('@/views/NotFound.vue')
  // }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation Guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Cek apakah sudah login
  const isAuthenticated = authStore.isAuthenticated
  
  // Jika belum fetch user dan ada token, fetch dulu
  if (isAuthenticated && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to fetch user:', error)
      return next('/login')
    }
  }

  // Halaman guest (login, register) - redirect ke dashboard jika sudah login
  if (to.meta.guest && isAuthenticated) {
    return next('/dashboard')
  }

  // Halaman yang butuh autentikasi
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next({
      path: '/login',
      query: { redirect: to.fullPath }
    })
  }

  // Cek role requirement
  if (to.meta.roles && isAuthenticated) {
    const hasRequiredRole = authStore.hasAnyRole(to.meta.roles)
    if (!hasRequiredRole) {
      return next('/dashboard') // atau halaman unauthorized
    }
  }

  // Cek permission requirement
  if (to.meta.permissions && isAuthenticated) {
    const hasRequiredPermission = authStore.hasAnyPermission(to.meta.permissions)
    if (!hasRequiredPermission) {
      return next('/dashboard')
    }
  }

  // Cek canApprove untuk halaman approval
  if (to.meta.requiresApproval && isAuthenticated) {
    if (!authStore.canApprove) {
      return next('/dashboard')
    }
  }

  next()
})

export default router