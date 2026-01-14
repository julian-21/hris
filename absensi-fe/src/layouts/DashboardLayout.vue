<template>
  <div class="min-h-screen bg-background">
    
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-card border-r border-border transition-transform duration-300 ease-in-out flex flex-col',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:translate-x-0' 
      ]"
    >
      <div class="flex h-14 items-center gap-2.5 px-4 border-b border-border">
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-primary-foreground">
          <Briefcase class="w-4 h-4" />
        </div>
        <div class="flex flex-col">
          <span class="font-semibold text-sm">HR System</span>
          <span class="text-[10px] text-muted-foreground uppercase tracking-wider">Enterprise</span>
        </div>
        
        <button 
          @click="sidebarOpen = false"
          class="ml-auto lg:hidden p-1.5 rounded-md hover:bg-accent transition-colors"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <div class="px-2 mb-2">
          <span class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Menu</span>
        </div>
        
        <router-link
          v-for="item in filteredMenuItems"
          :key="item.path"
          :to="item.path"
          class="group flex items-center justify-between px-2.5 py-2 text-sm font-medium rounded-md transition-colors"
          :class="[
            isActive(item.path)
              ? 'bg-accent text-accent-foreground'
              : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
          ]"
        >
          <div class="flex items-center gap-2.5">
            <component 
              :is="item.icon" 
              class="w-4 h-4 shrink-0"
            />
            <span>{{ item.label }}</span>
          </div>
          
          <span 
            v-if="item.badge" 
            class="flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] rounded-md font-semibold"
            :class="[
              isActive(item.path) 
                ? 'bg-background text-foreground' 
                : 'bg-muted text-muted-foreground'
            ]"
          >
            {{ item.badge }}
          </span>
        </router-link>
      </nav>

      <div class="p-3 border-t border-border">
        <div class="flex items-center gap-2.5 p-2 rounded-md hover:bg-accent transition-colors cursor-pointer group">
          <div class="relative flex items-center justify-center w-8 h-8 rounded-md bg-muted text-sm font-semibold">
            {{ userInitials }}
            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 border-2 border-card rounded-full"></span>
          </div>
          
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium truncate">{{ authStore.user?.name }}</p>
            <p class="text-[10px] text-muted-foreground truncate">{{ userRole }}</p>
          </div>
          
          <button 
            @click="logout" 
            class="p-1.5 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
            title="Logout"
          >
            <LogOut class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </aside>

    <div class="lg:pl-64 flex flex-col min-h-screen">
      
      <header class="sticky top-0 z-40 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="flex h-14 items-center gap-4 px-4">
          <button 
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden p-2 -ml-2 rounded-md hover:bg-accent transition-colors"
          >
            <Menu class="w-4 h-4" />
          </button>

          <nav class="hidden md:flex items-center text-sm">
            <ol class="flex items-center gap-2">
              <li>
                <router-link 
                  to="/dashboard" 
                  class="flex items-center justify-center w-7 h-7 rounded-md hover:bg-accent transition-colors"
                >
                  <Home class="w-3.5 h-3.5" />
                </router-link>
              </li>
              <li class="flex items-center gap-2 text-muted-foreground">
                <span>/</span>
                <span 
                  class="px-2 py-1 rounded-md transition-colors"
                  :class="route.path === '/dashboard' 
                    ? 'text-foreground bg-muted font-medium' 
                    : 'hover:text-foreground hover:bg-accent cursor-pointer'"
                >
                   {{ isDashboard ? 'Overview' : 'Pages' }}
                </span>
              </li>
              <li v-if="!isDashboard" class="flex items-center gap-2 text-muted-foreground">
                <span>/</span>
                <span class="px-2 py-1 rounded-md bg-primary text-primary-foreground font-medium text-xs">
                  {{ currentPageTitle }}
                </span>
              </li>
            </ol>
          </nav>

          <div class="ml-auto flex items-center gap-2">
            <SearchBar />

            <NotificationPanel />

            <div class="relative" v-click-outside="closeProfileDropdown">
              <button 
                @click="toggleProfileDropdown"
                class="flex items-center gap-2 outline-none"
              >
                <div class="h-8 w-8 rounded-md bg-muted flex items-center justify-center text-xs font-semibold hover:bg-accent transition-colors">
                  {{ userInitials }}
                </div>
              </button>

              <Transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
              >
                <div 
                  v-if="profileDropdown"
                  class="absolute right-0 top-full mt-2 w-56 rounded-lg border border-border bg-popover p-1 shadow-lg z-50"
                >
                  <div class="px-2 py-2 border-b border-border mb-1">
                    <div class="flex items-center gap-2 mb-2">
                      <div class="h-9 w-9 rounded-md bg-muted flex items-center justify-center text-sm font-semibold">
                        {{ userInitials }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ authStore.user?.name }}</p>
                        <p class="text-xs text-muted-foreground truncate">{{ authStore.user?.email }}</p>
                      </div>
                    </div>
                    <span class="inline-flex px-2 py-0.5 text-[10px] font-medium rounded-md bg-muted text-muted-foreground">
                      {{ userRole }}
                    </span>
                  </div>

                  <a href="#" class="flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-accent transition-colors">
                    <User class="w-4 h-4 text-muted-foreground" />
                    <span>Profile Settings</span>
                  </a>
                  <a href="#" class="flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-accent transition-colors">
                    <Settings class="w-4 h-4 text-muted-foreground" />
                    <span>Preferences</span>
                  </a>
                  <a href="#" class="flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-accent transition-colors">
                    <HelpCircle class="w-4 h-4 text-muted-foreground" />
                    <span>Help & Support</span>
                  </a>
                  
                  <div class="my-1 h-px bg-border"></div>
                  
                  <button 
                    @click="logout"
                    class="w-full flex items-center gap-2 rounded-md px-2 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors"
                  >
                    <LogOut class="w-4 h-4" />
                    <span>Log out</span>
                  </button>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 p-4 md:p-6">
        <div class="mx-auto max-w-7xl">
          <router-view v-slot="{ Component, route }">
            <Transition
              mode="out-in"
              enter-active-class="transition-opacity duration-150"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-100"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <component :is="Component" :key="route.path" />
            </Transition>
          </router-view>
        </div>
      </main>
    </div>

    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
      ></div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '@/composables/useToast';
import SearchBar from '@/components/SearchBar.vue';
import NotificationPanel from '@/components/NotificationPanel.vue';
import { 
  Home, Users, Calendar, Settings, 
  Menu, User, LogOut, BarChart3, 
  Clock, Briefcase, X, HelpCircle, Building2,
  ClipboardList, DollarSign
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { toast } = useToast();

const sidebarOpen = ref(false);
const profileDropdown = ref(false);
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

// Function: Fetch badge counts
const fetchBadges = async () => {
  try {
    const response = await fetch('https://mbg.erpdis.com/api/badges', {
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