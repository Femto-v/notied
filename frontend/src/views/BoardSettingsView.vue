<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { boardsApi } from '@/services/api'
import { useToastStore } from '@/stores/toast'
import { useForm, required, maxLen, email } from '@/composables/useForm'
import AppNav from '@/components/AppNav.vue'
import AppModal from '@/components/AppModal.vue'
import AppSpinner from '@/components/AppSpinner.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })
const router = useRouter()
const toast = useToastStore()

const loading = ref(true)
const board = ref(null)

const renameForm = useForm(
  { title: '', description: '' },
  { title: [required('Board name is required.'), maxLen(120)] }
)
const saving = ref(false)

// Collaborators are an M5 extension; demonstrated with local state + invite form.
const members = ref([
  { id: 1, name: 'Fadhil Raihan', email: 'demo@notied.app', permission: 'owner' },
])
const inviteForm = useForm(
  { email: '', permission: 'edit' },
  { email: [required('Enter an email to invite.'), email()] }
)
const inviting = ref(false)

async function load() {
  loading.value = true
  try {
    const data = await boardsApi.get(props.id)
    board.value = data
    renameForm.fields.title = data.title
    renameForm.fields.description = data.description || ''
  } catch {
    toast.error('Could not load board settings.')
    router.push({ name: 'dashboard' })
  } finally {
    loading.value = false
  }
}
onMounted(load)

async function saveDetails() {
  if (!renameForm.validate()) return
  saving.value = true
  try {
    await boardsApi.update(props.id, { ...renameForm.fields })
    toast.success('Board details updated.')
  } catch {
    toast.error('Could not save changes.')
  } finally {
    saving.value = false
  }
}

function sendInvite() {
  if (!inviteForm.validate()) return
  inviting.value = true
  setTimeout(() => {
    members.value.push({
      id: Date.now(), name: inviteForm.fields.email.split('@')[0],
      email: inviteForm.fields.email, permission: inviteForm.fields.permission,
    })
    toast.success(`Invite sent to ${inviteForm.fields.email}.`)
    inviteForm.reset()
    inviting.value = false
  }, 500)
}
function removeMember(m) {
  members.value = members.value.filter((x) => x.id !== m.id)
  toast.info(`${m.name} removed from board.`)
}

const showDelete = ref(false)
const deleting = ref(false)
async function deleteBoard() {
  deleting.value = true
  try {
    await boardsApi.remove(props.id)
    toast.success('Board deleted.')
    router.push({ name: 'dashboard' })
  } catch {
    toast.error('Could not delete the board.')
    deleting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-paper">
    <AppNav :show-search="false" />

    <main class="mx-auto max-w-2xl px-4 sm:px-6 py-8">
      <RouterLink :to="{ name: 'board', params: { id } }" class="inline-flex items-center gap-1.5 text-sm text-graphite hover:text-ink mb-5">
        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M12 5l-5 5 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back to board
      </RouterLink>

      <h1 class="font-display text-2xl font-extrabold mb-6">Board settings</h1>

      <div v-if="loading" class="flex justify-center py-12"><AppSpinner :size="24">Loading…</AppSpinner></div>

      <div v-else class="space-y-6">
        <!-- Details -->
        <section class="card p-5">
          <h2 class="font-display text-lg font-semibold mb-4">Details</h2>
          <form @submit.prevent="saveDetails" novalidate class="space-y-4">
            <div>
              <label for="s-title" class="label">Board name</label>
              <input id="s-title" v-model="renameForm.fields.title" class="field"
                :class="{ 'field-error': renameForm.errors.title }" @blur="renameForm.touch('title')" />
              <p v-if="renameForm.errors.title" class="mt-1 text-[12px] text-red-600">{{ renameForm.errors.title }}</p>
            </div>
            <div>
              <label for="s-desc" class="label">Description</label>
              <textarea id="s-desc" v-model="renameForm.fields.description" rows="2" class="field resize-none" />
            </div>
            <div class="flex justify-end">
              <button type="submit" class="btn-primary" :disabled="saving">
                <AppSpinner v-if="saving" :size="16" />{{ saving ? 'Saving…' : 'Save changes' }}
              </button>
            </div>
          </form>
        </section>

        <!-- Collaborators -->
        <section class="card p-5">
          <h2 class="font-display text-lg font-semibold">Collaborators</h2>
          <p class="text-sm text-graphite mt-1 mb-4">Invite teammates to view or edit this board.</p>

          <ul class="divide-y divide-hairline mb-4">
            <li v-for="m in members" :key="m.id" class="flex items-center gap-3 py-2.5">
              <span class="grid place-items-center w-8 h-8 rounded-full bg-pine-100 text-pine-700 text-xs font-semibold shrink-0">
                {{ m.name.slice(0,2).toUpperCase() }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate">{{ m.name }}</p>
                <p class="font-mono text-[11px] text-graphite truncate">{{ m.email }}</p>
              </div>
              <span v-if="m.permission === 'owner'" class="font-mono text-[10px] uppercase tracking-wider text-pine-600 bg-pine-50 px-2 py-1 rounded">Owner</span>
              <template v-else>
                <span class="font-mono text-[10px] uppercase tracking-wider text-graphite bg-cork px-2 py-1 rounded">{{ m.permission }}</span>
                <button class="p-1.5 rounded-md text-graphite/50 hover:text-red-500 hover:bg-red-50" @click="removeMember(m)" aria-label="Remove">
                  <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </button>
              </template>
            </li>
          </ul>

          <form @submit.prevent="sendInvite" novalidate class="flex flex-col sm:flex-row gap-2">
            <div class="flex-1">
              <input v-model="inviteForm.fields.email" type="email" class="field"
                :class="{ 'field-error': inviteForm.errors.email }" placeholder="teammate@example.com"
                @blur="inviteForm.touch('email')" />
              <p v-if="inviteForm.errors.email" class="mt-1 text-[12px] text-red-600">{{ inviteForm.errors.email }}</p>
            </div>
            <select v-model="inviteForm.fields.permission" class="field sm:w-28">
              <option value="view">Can view</option>
              <option value="edit">Can edit</option>
            </select>
            <button type="submit" class="btn-primary sm:self-start" :disabled="inviting">
              <AppSpinner v-if="inviting" :size="16" />Invite
            </button>
          </form>
        </section>

        <!-- Danger zone -->
        <section class="rounded-xl border border-red-200 bg-red-50/50 p-5">
          <h2 class="font-display text-lg font-semibold text-red-700">Danger zone</h2>
          <p class="text-sm text-red-600/80 mt-1 mb-4">Deleting a board removes all of its notes permanently.</p>
          <button class="btn-danger" @click="showDelete = true">Delete this board</button>
        </section>
      </div>
    </main>

    <AppModal :open="showDelete" title="Delete board?" @close="showDelete = false">
      <p class="text-sm text-graphite">
        “<span class="font-semibold text-ink">{{ board?.title }}</span>” and all its notes will be permanently
        removed. This can't be undone.
      </p>
      <div class="flex justify-end gap-2 mt-5">
        <button class="btn-ghost" @click="showDelete = false">Keep it</button>
        <button class="btn-danger" :disabled="deleting" @click="deleteBoard">
          <AppSpinner v-if="deleting" :size="16" />{{ deleting ? 'Deleting…' : 'Delete board' }}
        </button>
      </div>
    </AppModal>
  </div>
</template>
