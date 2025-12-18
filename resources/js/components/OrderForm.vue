<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Place Order</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Symbol Selector -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Symbol</label>
        <select
          v-model="form.symbol"
          @change="handleSymbolChange"
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500"
        >
          <option value="BTC">BTC/USD</option>
          <option value="ETH">ETH/USD</option>
          <option value="SOL">SOL/USD</option>
          <option value="XRP">XRP/USD</option>
        </select>
      </div>

      <!-- Side Toggle -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Side</label>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            @click="form.side = 'buy'"
            :class="[
              'px-4 py-2 rounded-md font-medium transition',
              form.side === 'buy'
                ? 'bg-green-600 text-white'
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            ]"
          >
            Buy
          </button>
          <button
            type="button"
            @click="form.side = 'sell'"
            :class="[
              'px-4 py-2 rounded-md font-medium transition',
              form.side === 'sell'
                ? 'bg-red-600 text-white'
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            ]"
          >
            Sell
          </button>
        </div>
      </div>

      <!-- Price Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Price (USD)</label>
        <input
          v-model="form.price"
          type="number"
          step="0.00000001"
          min="0"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500"
          placeholder="0.00000000"
        />
      </div>

      <!-- Amount Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Amount ({{ form.symbol }})
        </label>
        <input
          v-model="form.amount"
          type="number"
          step="0.00000001"
          min="0"
          required
          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500"
          placeholder="0.00000000"
        />
        <p v-if="form.side === 'sell'" class="mt-1 text-xs text-gray-500">
          Available: {{ formatNumber(tradingStore.getAssetBalance(form.symbol)) }}
        </p>
      </div>

      <!-- Order Summary -->
      <div v-if="orderTotal > 0" class="p-4 bg-gray-50 rounded-md space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Total Value:</span>
          <span class="font-medium">${{ formatNumber(orderTotal) }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Commission (1.5%):</span>
          <span class="font-medium text-orange-600">${{ formatNumber(commission) }}</span>
        </div>
        <div class="flex justify-between text-sm font-bold border-t pt-2">
          <span>{{ form.side === 'buy' ? 'Total Cost:' : 'You Receive:' }}</span>
          <span>${{ formatNumber(form.side === 'buy' ? finalTotal : orderTotal) }}</span>
        </div>
      </div>

      <!-- Balance Check -->
      <div v-if="form.side === 'buy' && finalTotal > parseFloat(tradingStore.balance)" 
           class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
        Insufficient USD balance
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="loading || !canSubmit"
        :class="[
          'w-full py-3 px-4 rounded-md font-medium transition',
          form.side === 'buy'
            ? 'bg-green-600 hover:bg-green-700 text-white'
            : 'bg-red-600 hover:bg-red-700 text-white',
          'disabled:opacity-50 disabled:cursor-not-allowed'
        ]"
      >
        {{ loading ? 'Placing Order...' : `Place ${form.side.toUpperCase()} Order` }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useTradingStore } from '../stores/trading'

const tradingStore = useTradingStore()

const loading = ref(false)
const form = reactive({
  symbol: 'BTC',
  side: 'buy',
  price: '',
  amount: '',
})

const orderTotal = computed(() => {
  const price = parseFloat(form.price) || 0
  const amount = parseFloat(form.amount) || 0
  return price * amount
})

const commission = computed(() => {
  return orderTotal.value * 0.015
})

const finalTotal = computed(() => {
  return orderTotal.value + commission.value
})

const canSubmit = computed(() => {
  if (!form.price || !form.amount) return false
  if (form.side === 'buy' && finalTotal.value > parseFloat(tradingStore.balance)) return false
  if (form.side === 'sell' && parseFloat(form.amount) > parseFloat(tradingStore.getAssetBalance(form.symbol))) return false
  return true
})

const handleSymbolChange = () => {
  tradingStore.fetchOrderbook(form.symbol)
}

const handleSubmit = async () => {
  loading.value = true
  try {
    await tradingStore.createOrder({
      symbol: form.symbol,
      side: form.side,
      price: form.price,
      amount: form.amount,
    })
    
    // Reset form
    form.price = ''
    form.amount = ''
  } catch (error) {
    console.error('Failed to create order:', error)
  } finally {
    loading.value = false
  }
}

const formatNumber = (value) => {
  return parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 8,
  })
}

// Load orderbook on mount
watch(() => form.symbol, (newSymbol) => {
  tradingStore.fetchOrderbook(newSymbol)
}, { immediate: true })
</script>