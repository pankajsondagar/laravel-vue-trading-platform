<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-indigo-600">Crypto Exchange</h1>
          </div>
          <div class="flex items-center space-x-4">
            <template v-if="authStore.isAuthenticated">
              <span class="text-gray-700">{{ authStore.user?.name }}</span>
              <button
                @click="handleLogout"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
              >
                Logout
              </button>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <LoginView v-if="!authStore.isAuthenticated" />
      <TradingDashboard v-else />
    </main>

    <!-- Toast Notifications -->
    <Toaster />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import LoginView from './components/LoginView.vue'
import TradingDashboard from './components/TradingDashboard.vue'
import Toaster from './components/Toaster.vue'

const authStore = useAuthStore()

onMounted(() => {
  authStore.checkAuth()
})

const handleLogout = async () => {
  await authStore.logout()
}
</script>