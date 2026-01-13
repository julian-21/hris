<template>
  <div class="relative">
    <!-- Main Card -->
    <Card class="hover:shadow-md transition-shadow relative z-10">
      <CardContent class="p-4 pt-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4 flex-1">
            <!-- Toggle Button -->
            <Button
              v-if="employee.bawahan && employee.bawahan.length > 0"
              @click="expanded = !expanded"
              variant="ghost"
              size="sm"
              class="h-8 w-8 p-0 shrink-0">
              <ChevronRight v-if="!expanded" class="w-5 h-5" />
              <ChevronDown v-else class="w-5 h-5" />
            </Button>
            <div v-else class="w-8 shrink-0"></div>

            <!-- Employee Info -->
            <Avatar class="w-12 h-12 border-2 border-gray-200 shrink-0">
              <AvatarImage 
                :src="employee.picture || getDefaultAvatar(employee.name)" 
                :alt="employee.name" />
              <AvatarFallback class="bg-gray-100 text-gray-600">
                {{ getInitials(employee.name) }}
              </AvatarFallback>
            </Avatar>
            
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h4 class="font-semibold text-gray-900 truncate">{{ employee.name }}</h4>
                <Badge 
                  v-if="level === 0" 
                  variant="secondary"
                  class="text-xs shrink-0">
                  Top Level
                </Badge>
              </div>
              <p class="text-sm text-gray-600 mb-1 truncate">{{ employee.email }}</p>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-gray-600">{{ employee.posisi }}</span>
                <Separator orientation="vertical" class="h-3" />
                <Badge variant="outline" class="text-xs">
                  {{ employee.role }}
                </Badge>
                <span v-if="employee.kantor" class="text-xs text-gray-600 flex items-center gap-1">
                  <MapPin class="w-3 h-3" />
                  {{ employee.kantor.nama }}
                </span>
              </div>
            </div>

            <!-- Stats Badge -->
            <div v-if="employee.bawahan && employee.bawahan.length > 0" class="shrink-0">
              <Card class="border-none bg-gray-50">
                <CardContent class="p-3 pt-4 text-center">
                  <div class="text-2xl font-bold text-gray-900">{{ employee.bawahan.length }}</div>
                  <div class="text-xs text-gray-600">Bawahan</div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Children Tree Structure -->
    <div v-if="expanded && employee.bawahan && employee.bawahan.length > 0" class="relative mt-6 ml-6">
      <!-- Vertical Line dari Parent -->
      <div class="absolute left-0 top-0 w-px h-6 bg-gray-300"></div>
      
      <div class="space-y-6">
        <div 
          v-for="(bawahan, index) in employee.bawahan" 
          :key="bawahan.id"
          class="relative pl-12">
          <!-- Horizontal Line ke Child -->
          <div 
            class="absolute left-0 w-12 bg-gray-300"
            :class="index === employee.bawahan.length - 1 ? 'h-px top-6' : 'h-px top-6'">
          </div>
          
          <!-- Vertical Line untuk bukan child terakhir -->
          <div 
            v-if="index !== employee.bawahan.length - 1"
            class="absolute left-0 top-6 w-px bg-gray-300"
            :style="{ height: 'calc(100% + 24px)' }">
          </div>
          
          <!-- Corner untuk sambungan -->
          <div 
            v-if="index === employee.bawahan.length - 1"
            class="absolute left-0 top-0 w-px h-6 bg-gray-300">
          </div>
          
          <EmployeeHierarchyNode :employee="bawahan" :level="level + 1" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ChevronRight, ChevronDown, MapPin } from 'lucide-vue-next';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

const props = defineProps({
  employee: Object,
  level: {
    type: Number,
    default: 0
  }
});

const expanded = ref(props.level < 2); // Auto expand 2 level pertama

const getInitials = (name) => {
  return name.split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const getDefaultAvatar = (name) => {
  const initials = getInitials(name);
  return `https://ui-avatars.com/api/?name=${initials}&background=6b7280&color=fff&size=200`;
};
</script>