<script setup>
import { ref } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm, required, email, minLen } from '@/composables/useForm'
import { USE_MOCK } from '@/services/mock'
import AuthLayout from '@/components/AuthLayout.vue'
import AppSpinner from '@/components/AppSpinner.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const toast = useToastStore()

const form = useForm(
  { email: '', password: '' },
  {
    email: [required('Email is required.'), email()],
    password: [required('Password is required.'), minLen(6, 'Password is too short.')],
  }
)

const submitting = ref(false)

async function submit() {
  if (!form.validate()) return
  submitting.value = true
  try {
    await auth.login({ email: form.fields.email, password: form.fields.password })
    toast.success(`Welcome back, ${auth.user?.name?.split(' ')[0] || 'friend'}!`)
    router.push(route.query.redirect || { name: 'dashboard' })
  } catch (err) {
    const status = err.response?.status
    if (status === 401) {
      form.errors.password = 'Email or password is incorrect.'
    } else if (status === 422) {
      form.setServerErrors(err.response.data?.errors)
    } else {
      toast.error('Could not sign in. Please try again.')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="mb-6">
      <h2 class="font-display text-2xl font-extrabold">Welcome back</h2>
      <p class="text-sm text-graphite mt-1">Sign in to your Notied workspace.</p>
    </div>

    <p v-if="USE_MOCK" class="mb-5 rounded-lg bg-sticky-yellow/50 border border-sticky-yellow px-3 py-2 text-[12px] text-ink/70 font-mono">
      Demo mode — try <b>demo@notied.app</b> / <b>password123</b>
    </p>

    <form @submit.prevent="submit" novalidate class="space-y-4">
      <div>
        <label for="email" class="label">Email</label>
        <input
          id="email" v-model="form.fields.email" type="email" autocomplete="email"
          class="field" :class="{ 'field-error': form.touched.email && form.errors.email }"
          placeholder="you@example.com" @blur="form.touch('email')"
        />
        <p v-if="form.touched.email && form.errors.email" class="mt-1 text-[12px] text-red-600">{{ form.errors.email }}</p>
      </div>

      <div>
        <label for="password" class="label">Password</label>
        <input
          id="password" v-model="form.fields.password" type="password" autocomplete="current-password"
          class="field" :class="{ 'field-error': form.touched.password && form.errors.password }"
          placeholder="••••••••" @blur="form.touch('password')"
        />
        <p v-if="form.touched.password && form.errors.password" class="mt-1 text-[12px] text-red-600">{{ form.errors.password }}</p>
      </div>

      <button type="submit" class="btn-primary w-full" :disabled="submitting">
        <AppSpinner v-if="submitting" :size="16" />
        <span>{{ submitting ? 'Signing in…' : 'Sign in' }}</span>
      </button>
    </form>

    <div class="my-5 flex items-center gap-3 text-graphite/60">
      <span class="h-px flex-1 bg-hairline" /><span class="text-[11px] font-mono uppercase tracking-wider">or</span><span class="h-px flex-1 bg-hairline" />
    </div>

    <button class="btn-outline w-full" type="button" @click="toast.info('Google sign-in is a planned extension.')">
      <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.5 12.2c0-.7-.1-1.4-.2-2H12v3.9h5.9a5 5 0 0 1-2.2 3.3v2.7h3.5c2-1.9 3.2-4.7 3.2-7.9Z"/><path fill="#34A853" d="M12 23c2.9 0 5.4-1 7.2-2.6l-3.5-2.7c-1 .6-2.2 1-3.7 1-2.8 0-5.2-1.9-6.1-4.5H2.3v2.8A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.9 14.2a6.6 6.6 0 0 1 0-4.2V7.2H2.3a11 11 0 0 0 0 9.8l3.6-2.8Z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3 .5 4.1 1.6l3-3A11 11 0 0 0 2.3 7.2L5.9 10c.9-2.6 3.3-4.6 6.1-4.6Z"/></svg>
      Continue with Google
    </button>

    <p class="mt-6 text-center text-sm text-graphite">
      No account?
      <RouterLink :to="{ name: 'register' }" class="font-semibold text-pine-600 hover:text-pine-700">Register free</RouterLink>
    </p>
  </AuthLayout>
</template>
