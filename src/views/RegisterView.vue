<script setup>
import { ref, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { useForm, required, email, minLen, maxLen } from '@/composables/useForm'
import AuthLayout from '@/components/AuthLayout.vue'
import AppSpinner from '@/components/AppSpinner.vue'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const form = useForm(
  { name: '', email: '', password: '' },
  {
    name: [required('Tell us your name.'), maxLen(100)],
    email: [required('Email is required.'), email()],
    password: [required('Choose a password.'), minLen(8, 'Use at least 8 characters.')],
  }
)

const submitting = ref(false)

// Live password strength meter (UX feedback, not a hard rule).
const strength = computed(() => {
  const p = form.fields.password
  let s = 0
  if (p.length >= 8) s++
  if (/[A-Z]/.test(p)) s++
  if (/[0-9]/.test(p)) s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})
const strengthLabel = ['Too short', 'Weak', 'Okay', 'Good', 'Strong']
const strengthColor = ['bg-red-300', 'bg-red-400', 'bg-sticky-orange', 'bg-pine-300', 'bg-pine-500']

async function submit() {
  if (!form.validate()) return
  submitting.value = true
  try {
    await auth.register({ ...form.fields })
    toast.success('Account created — welcome to Notied!')
    router.push({ name: 'dashboard' })
  } catch (err) {
    const status = err.response?.status
    if (status === 409) {
      form.errors.email = 'That email is already registered.'
    } else if (status === 422) {
      form.setServerErrors(err.response.data?.errors)
    } else {
      toast.error('Could not create account. Please try again.')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="mb-6">
      <h2 class="font-display text-2xl font-extrabold">Create account</h2>
      <p class="text-sm text-graphite mt-1">Start organising your ideas today.</p>
    </div>

    <form @submit.prevent="submit" novalidate class="space-y-4">
      <div>
        <label for="name" class="label">Full name</label>
        <input
          id="name" v-model="form.fields.name" type="text" autocomplete="name"
          class="field" :class="{ 'field-error': form.touched.name && form.errors.name }"
          placeholder="Fadhil Raihan" @blur="form.touch('name')"
        />
        <p v-if="form.touched.name && form.errors.name" class="mt-1 text-[12px] text-red-600">{{ form.errors.name }}</p>
      </div>

      <div>
        <label for="r-email" class="label">Email</label>
        <input
          id="r-email" v-model="form.fields.email" type="email" autocomplete="email"
          class="field" :class="{ 'field-error': form.touched.email && form.errors.email }"
          placeholder="you@example.com" @blur="form.touch('email')"
        />
        <p v-if="form.touched.email && form.errors.email" class="mt-1 text-[12px] text-red-600">{{ form.errors.email }}</p>
      </div>

      <div>
        <label for="r-password" class="label">Password</label>
        <input
          id="r-password" v-model="form.fields.password" type="password" autocomplete="new-password"
          class="field" :class="{ 'field-error': form.touched.password && form.errors.password }"
          placeholder="Create a strong password" @blur="form.touch('password')" @input="form.validateField('password')"
        />
        <div v-if="form.fields.password" class="mt-2 flex items-center gap-2">
          <div class="flex-1 flex gap-1">
            <span v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-colors"
              :class="i <= strength ? strengthColor[strength] : 'bg-hairline'" />
          </div>
          <span class="font-mono text-[11px] text-graphite">{{ strengthLabel[strength] }}</span>
        </div>
        <p v-if="form.touched.password && form.errors.password" class="mt-1 text-[12px] text-red-600">{{ form.errors.password }}</p>
      </div>

      <button type="submit" class="btn-primary w-full" :disabled="submitting">
        <AppSpinner v-if="submitting" :size="16" />
        <span>{{ submitting ? 'Creating…' : 'Create account' }}</span>
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-graphite">
      Already have one?
      <RouterLink :to="{ name: 'login' }" class="font-semibold text-pine-600 hover:text-pine-700">Sign in</RouterLink>
    </p>
  </AuthLayout>
</template>
