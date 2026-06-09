<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { profileApi } from '@/services/api'
import { useForm, required, minLen, matches } from '@/composables/useForm'
import AppNav from '@/components/AppNav.vue'
import AppSpinner from '@/components/AppSpinner.vue'

const auth = useAuthStore()
const toast = useToastStore()

const initials = computed(() => {
  const n = auth.user?.name || auth.user?.email || '?'
  return n.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('')
})

const profileForm = useForm(
  { name: auth.user?.name || '' },
  { name: [required('Name is required.')] }
)
const savingProfile = ref(false)

onMounted(() => { profileForm.fields.name = auth.user?.name || '' })

async function saveProfile() {
  if (!profileForm.validate()) return
  savingProfile.value = true
  try {
    const updated = await profileApi.update({ name: profileForm.fields.name })
    auth.user = { ...auth.user, ...updated }
    localStorage.setItem('notied_user', JSON.stringify(auth.user))
    toast.success('Profile updated.')
  } catch {
    toast.error('Could not update profile.')
  } finally {
    savingProfile.value = false
  }
}

const pwForm = useForm(
  { current: '', next: '', confirm: '' },
  {
    current: [required('Enter your current password.')],
    next: [required('Choose a new password.'), minLen(8, 'Use at least 8 characters.')],
    confirm: [required('Confirm your new password.'), matches(() => pwForm.fields.next, 'Passwords do not match.')],
  }
)
const savingPw = ref(false)

async function changePassword() {
  if (!pwForm.validate()) return
  savingPw.value = true
  try {
    await profileApi.changePassword({ current: pwForm.fields.current, next: pwForm.fields.next })
    toast.success('Password changed.')
    pwForm.reset()
  } catch (err) {
    if (err.response?.status === 401) pwForm.errors.current = 'Current password is incorrect.'
    else toast.error('Could not change password.')
  } finally {
    savingPw.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-paper">
    <AppNav :show-search="false" />

    <main class="mx-auto max-w-2xl px-4 sm:px-6 py-8">
      <RouterLink :to="{ name: 'dashboard' }" class="inline-flex items-center gap-1.5 text-sm text-graphite hover:text-ink mb-5">
        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M12 5l-5 5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back to boards
      </RouterLink>

      <h1 class="font-display text-2xl font-extrabold mb-6">Profile</h1>

      <div class="space-y-6">
        <!-- Identity -->
        <section class="card p-5">
          <div class="flex items-center gap-4 mb-5">
            <span class="grid place-items-center w-16 h-16 rounded-full bg-pine-500 text-white text-xl font-display font-bold">
              {{ initials }}
            </span>
            <div>
              <p class="font-display text-lg font-semibold">{{ auth.user?.name }}</p>
              <p class="font-mono text-[12px] text-graphite">{{ auth.user?.email }}</p>
            </div>
          </div>

          <form @submit.prevent="saveProfile" novalidate class="space-y-4">
            <div>
              <label for="p-name" class="label">Display name</label>
              <input id="p-name" v-model="profileForm.fields.name" class="field"
                :class="{ 'field-error': profileForm.errors.name }" @blur="profileForm.touch('name')" />
              <p v-if="profileForm.errors.name" class="mt-1 text-[12px] text-red-600">{{ profileForm.errors.name }}</p>
            </div>
            <div>
              <label for="p-email" class="label">Email</label>
              <input id="p-email" :value="auth.user?.email" class="field bg-cork/50 text-graphite cursor-not-allowed" disabled />
              <p class="mt-1 text-[12px] text-graphite">Email can't be changed in this version.</p>
            </div>
            <div class="flex justify-end">
              <button type="submit" class="btn-primary" :disabled="savingProfile">
                <AppSpinner v-if="savingProfile" :size="16" />{{ savingProfile ? 'Saving…' : 'Save profile' }}
              </button>
            </div>
          </form>
        </section>

        <!-- Password -->
        <section class="card p-5">
          <h2 class="font-display text-lg font-semibold mb-4">Change password</h2>
          <form @submit.prevent="changePassword" novalidate class="space-y-4">
            <div>
              <label for="pw-cur" class="label">Current password</label>
              <input id="pw-cur" v-model="pwForm.fields.current" type="password" autocomplete="current-password"
                class="field" :class="{ 'field-error': pwForm.errors.current }" @blur="pwForm.touch('current')" />
              <p v-if="pwForm.errors.current" class="mt-1 text-[12px] text-red-600">{{ pwForm.errors.current }}</p>
            </div>
            <div>
              <label for="pw-new" class="label">New password</label>
              <input id="pw-new" v-model="pwForm.fields.next" type="password" autocomplete="new-password"
                class="field" :class="{ 'field-error': pwForm.errors.next }" @blur="pwForm.touch('next')" />
              <p v-if="pwForm.errors.next" class="mt-1 text-[12px] text-red-600">{{ pwForm.errors.next }}</p>
            </div>
            <div>
              <label for="pw-conf" class="label">Confirm new password</label>
              <input id="pw-conf" v-model="pwForm.fields.confirm" type="password" autocomplete="new-password"
                class="field" :class="{ 'field-error': pwForm.errors.confirm }" @blur="pwForm.touch('confirm')" />
              <p v-if="pwForm.errors.confirm" class="mt-1 text-[12px] text-red-600">{{ pwForm.errors.confirm }}</p>
            </div>
            <div class="flex justify-end">
              <button type="submit" class="btn-primary" :disabled="savingPw">
                <AppSpinner v-if="savingPw" :size="16" />{{ savingPw ? 'Updating…' : 'Update password' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </main>
  </div>
</template>
