<template>
  <div class="min-h-screen bg-zinc-50/50">
    
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-zinc-200 transition-transform duration-300 ease-in-out flex flex-col',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:translate-x-0' 
      ]"
    >
      <div class="flex h-16 items-center gap-2 px-6 border-b border-zinc-200 bg-white flex-shrink-0">
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-zinc-950 text-white shadow-sm">
          <Briefcase class="w-4 h-4" />
        </div>
        <div class="flex flex-col">
          <span class="font-bold text-sm tracking-tight text-zinc-950">HR System</span>
          <span class="text-[10px] font-medium text-zinc-500 uppercase tracking-wider">Enterprise</span>
        </div>
        
        <button 
          @click="sidebarOpen = false"
          class="ml-auto lg:hidden p-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <div class="px-3 mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">
          Menu
        </div>
        
        <router-link
          v-for="item in filteredMenuItems"
          :key="item.path"
          :to="item.path"
          class="group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition-all duration-200"
          :class="[
            isActive(item.path)
              ? 'bg-zinc-900 text-white shadow-sm'
              : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
          ]"
        >
          <div class="flex items-center gap-3">
            <component 
              :is="item.icon" 
              class="w-4 h-4 transition-colors"
              :class="isActive(item.path) ? 'text-zinc-300' : 'text-zinc-400 group-hover:text-zinc-600'"
            />
            <span>{{ item.label }}</span>
          </div>
          
          <span 
            v-if="item.badge" 
            class="px-2 py-0.5 text-[10px] rounded-full border font-semibold"
            :class="isActive(item.path) 
              ? 'bg-zinc-700 border-zinc-600 text-zinc-100' 
              : 'bg-zinc-100 border-zinc-200 text-zinc-600'"
          >
            {{ item.badge }}
          </span>
        </router-link>
      </div>

      <div class="p-4 border-t border-zinc-200 bg-white flex-shrink-0">
        <div class="flex items-center gap-3 w-full p-2 rounded-lg hover:bg-zinc-50 border border-transparent hover:border-zinc-200 transition-all cursor-pointer group">
          <div class="relative w-9 h-9 flex items-center justify-center rounded-full bg-zinc-200 text-zinc-700 font-semibold text-xs border border-zinc-300">
            {{ userInitials }}
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full"></span>
          </div>
          
          <div class="flex-1 min-w-0 text-left">
            <p class="text-sm font-medium text-zinc-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-xs text-zinc-500 truncate">{{ userRole }}</p>
          </div>
          
          <button 
            @click="logout" 
            class="p-1.5 rounded-md text-zinc-400 hover:text-red-600 hover:bg-red-50 transition-colors"
            title="Logout"
          >
            <LogOut class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <div class="lg:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
      
      <header class="sticky top-0 z-40 w-full border-b border-zinc-200 bg-white/80 backdrop-blur-sm">
        <div class="flex h-16 items-center gap-4 px-6">
          <button 
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden p-2 -ml-2 rounded-md text-zinc-500 hover:bg-zinc-100"
          >
            <Menu class="w-5 h-5" />
          </button>

          <nav class="hidden md:flex items-center text-sm font-medium">
            <ol class="flex items-center gap-2">
              <li>
                <router-link to="/dashboard" class="text-zinc-500 hover:text-zinc-900 transition-colors">
                  <Home class="w-4 h-4" />
                </router-link>
              </li>
              <li class="flex items-center gap-2 text-zinc-400">
                <span class="select-none">/</span>
                <span :class="route.path === '/dashboard' ? 'text-zinc-900' : 'text-zinc-500 hover:text-zinc-900 transition-colors cursor-pointer'">
                   {{ isDashboard ? 'Overview' : 'Pages' }}
                </span>
              </li>
              <li v-if="!isDashboard" class="flex items-center gap-2 text-zinc-400">
                <span class="select-none">/</span>
                <span class="text-zinc-900 font-semibold">{{ currentPageTitle }}</span>
              </li>
            </ol>
          </nav>

          <div class="ml-auto flex items-center gap-4">
            <div class="hidden md:flex relative group">
              <Search class="absolute left-2.5 top-2.5 w-4 h-4 text-zinc-400 group-focus-within:text-zinc-600 transition-colors" />
              <input 
                v-model="searchQuery"
                @keyup.enter="handleSearch"
                type="text" 
                placeholder="Search..." 
                class="h-9 w-64 rounded-md border border-zinc-200 bg-zinc-50 pl-9 pr-8 text-sm outline-none transition-all placeholder:text-zinc-400 focus:border-zinc-400 focus:bg-white focus:ring-4 focus:ring-zinc-100"
              />
              <button 
                v-if="searchQuery" 
                @click="searchQuery = ''"
                class="absolute right-2 top-2.5 text-zinc-400 hover:text-zinc-600"
              >
                 <X class="w-4 h-4" />
              </button>
              <div v-else class="absolute right-2 top-2 hidden lg:flex h-5 items-center gap-1 rounded border border-zinc-200 bg-white px-1.5 font-mono text-[10px] font-medium text-zinc-500">
                <span class="text-xs">⌘</span>K
              </div>
            </div>

            <button class="relative rounded-md p-2 text-zinc-500 hover:bg-zinc-100 transition-colors">
              <Bell class="w-5 h-5" />
              <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-600 ring-2 ring-white"></span>
            </button>

            <div class="relative" v-click-outside="closeProfileDropdown">
              <button 
                @click="toggleProfileDropdown"
                class="flex items-center gap-2 outline-none group"
              >
                <div class="h-8 w-8 rounded-full bg-zinc-100 border border-zinc-200 flex items-center justify-center text-xs font-medium text-zinc-700 transition-shadow group-hover:ring-2 group-hover:ring-zinc-100">
                  {{ userInitials }}
                </div>
              </button>

              <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95 -translate-y-1"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 -translate-y-1"
              >
                <div 
                  v-if="profileDropdown"
                  class="absolute right-0 top-full mt-2 w-56 rounded-md border border-zinc-200 bg-white p-1 shadow-lg z-50"
                >
                  <div class="px-2 py-1.5 border-b border-zinc-100 mb-1">
                    <p class="text-sm font-medium text-zinc-900">{{ authStore.user?.name }}</p>
                    <p class="text-xs text-zinc-500 truncate">{{ authStore.user?.email }}</p>
                    <span class="inline-flex mt-1 px-2 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-700">
                      {{ userRole }}
                    </span>
                  </div>

                  <a href="#" class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">
                    <User class="w-4 h-4 text-zinc-500" />
                    Profile
                  </a>
                  <a href="#" class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">
                    <Settings class="w-4 h-4 text-zinc-500" />
                    Settings
                  </a>
                  <a href="#" class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">
                    <HelpCircle class="w-4 h-4 text-zinc-500" />
                    Help
                  </a>
                  
                  <div class="my-1 h-px bg-zinc-100"></div>
                  
                  <button 
                    @click="logout"
                    class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
                  >
                    <LogOut class="w-4 h-4" />
                    Log out
                  </button>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 p-6 lg:p-8">
        <div class="mx-auto max-w-6xl animate-in fade-in slide-in-from-bottom-4 duration-500 ease-out">
          <router-view />
        </div>
      </main>
    </div>

    <Transition
      enter-active-class="transition-opacity ease-linear duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity ease-linear duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden backdrop-blur-sm"
      ></div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '@/composables/useToast'
import { 
  Home, Users, Calendar, FileText, Settings, 
  Menu, Search, Bell, User, LogOut, BarChart3, 
  Clock, Briefcase, X, HelpCircle, Building2,
  ClipboardList, DollarSign, UserCog
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { toast } = useToast()

const sidebarOpen = ref(false);
const profileDropdown = ref(false);
const searchQuery = ref('');
const badges = ref({
  employees: 0,
  leave: 0,
  overtime: 0
});

// Definisi semua menu dengan roles yang diizinkan
const allMenuItems = computed(() => [
  { 
    path: '/dashboard', 
    label: 'Dashboard', 
    icon: Home,
    roles: ['Admin', 'HR', 'Direktur', 'Karyawan', 'Sales', 'PPIC', 'Produksi', 'Finance', 'Digital Lead', 'Digital Public Relation', 'Bisdev', 'Sosmed Specialis', 'Engineer', 'CSO', 'PC', 'Canvassing Agent']
  },
  { 
    path: '/employee', 
    label: 'Karyawan', 
    icon: Users,
    badge: badges.value.employees || null,
    roles: ['Admin', 'HR', 'Direktur']
  },
  { 
    path: '/attendance', 
    label: 'Kehadiran', 
    icon: Clock,
    roles: ['Admin', 'HR', 'Direktur', 'Karyawan', 'Sales', 'PPIC', 'Produksi', 'Finance', 'Digital Lead', 'Digital Public Relation', 'Bisdev', 'Sosmed Specialis', 'Engineer', 'CSO', 'PC', 'Canvassing Agent']
  },
  { 
    path: '/leave', 
    label: 'Manajemen Cuti', 
    icon: Calendar,
    badge: badges.value.leave || null,
    roles: ['Admin', 'HR', 'Direktur', 'Karyawan', 'Sales', 'PPIC', 'Produksi', 'Finance', 'Digital Lead', 'Digital Public Relation', 'Bisdev', 'Sosmed Specialis', 'Engineer', 'CSO', 'PC', 'Canvassing Agent']
  },
  { 
    path: '/overtime', 
    label: 'Lembur', 
    icon: ClipboardList,
    badge: badges.value.overtime || null,
    roles: ['Admin', 'HR', 'Direktur', 'Karyawan', 'Sales', 'PPIC', 'Produksi', 'Finance', 'Digital Lead', 'Digital Public Relation', 'Bisdev', 'Sosmed Specialis', 'Engineer', 'CSO', 'PC', 'Canvassing Agent']
  },
  { 
    path: '/claim-lembur', 
    label: 'Claim Lembur', 
    icon: ClipboardList,
    badge: badges.value.overtime || null,
    roles: ['Admin', 'HR', 'Direktur', 'Karyawan', 'Sales', 'PPIC', 'Produksi', 'Finance', 'Digital Lead', 'Digital Public Relation', 'Bisdev', 'Sosmed Specialis', 'Engineer', 'CSO', 'PC', 'Canvassing Agent']
  },
  { 
    path: '/kantors', 
    label: 'Kantor', 
    icon: Building2,
    roles: ['Admin', 'HR', 'Direktur']
  },
  { 
    path: '/payroll', 
    label: 'Penggajian', 
    icon: DollarSign,
    roles: ['Admin', 'HR', 'Direktur', 'Finance']
  },
  { 
    path: '/reports', 
    label: 'Laporan Kehadiran', 
    icon: BarChart3,
    roles: ['Admin', 'HR', 'Direktur']
  },
  { 
    path: '/settings', 
    label: 'Pengaturan', 
    icon: Settings,
    roles: ['Admin', 'HR', 'Direktur']
  },
]);

// Computed: User role
const userRole = computed(() => {
  const roles = authStore.user?.roles;
  if (!roles || roles.length === 0) return 'Karyawan';
  return roles[0]?.name || 'Karyawan';
});

// Computed: Filter menu berdasarkan role
const filteredMenuItems = computed(() => {
  const role = userRole.value;
  return allMenuItems.value.filter(item => {
    // Jika tidak ada definisi roles, berarti untuk semua
    if (!item.roles || item.roles.length === 0) return true;
    // Cek apakah role user ada di dalam array roles yang diizinkan
    return item.roles.includes(role);
  });
});

// Computed: User initials
const userInitials = computed(() => {
  const name = authStore.user?.name || 'User';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

// Computed: Check if current page is dashboard
const isDashboard = computed(() => route.path === '/dashboard');

// Computed: Current page title
const currentPageTitle = computed(() => {
  const item = filteredMenuItems.value.find(i => i.path === route.path);
  return item?.label || 'Page';
});

// Function: Check if menu is active
const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/');
};

// Function: Toggle profile dropdown
const toggleProfileDropdown = () => {
  profileDropdown.value = !profileDropdown.value;
};

// Function: Close profile dropdown
const closeProfileDropdown = () => {
  profileDropdown.value = false;
};

// Function: Logout
const logout = async () => {
  profileDropdown.value = false;
  await authStore.logout();
  router.push('/login');
};

// Function: Handle search
const handleSearch = () => {
  if (!searchQuery.value.trim()) return;
  
  console.log('Searching for:', searchQuery.value);
  toast({
    title: "Mencari...",
    description: `Hasil pencarian untuk: "${searchQuery.value}"`,
  });
};

// Function: Fetch badge counts
const fetchBadges = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/badges', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      badges.value = data.badges;
    }
  } catch (error) {
    console.error('Error fetching badges:', error);
  }
};

// Directive: Click outside
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  },
};

// Lifecycle: On mounted
onMounted(() => {
  // Fetch badges saat component mounted
  fetchBadges();
  
  // Auto refresh badges setiap 30 detik
  const badgeInterval = setInterval(fetchBadges, 30000);
  
  const unwatch = router.afterEach(() => {
    sidebarOpen.value = false;
    profileDropdown.value = false;
    // Refresh badges saat navigasi
    fetchBadges();
  });
  
  // Cleanup
  return () => {
    unwatch();
    clearInterval(badgeInterval);
  };
});
</script>

<style scoped>
@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slide-in-from-bottom-4 {
  from { transform: translateY(1rem); }
  to { transform: translateY(0); }
}

.animate-in {
  animation: fade-in 0.5s ease-out, slide-in-from-bottom-4 0.5s ease-out;
}
</style>