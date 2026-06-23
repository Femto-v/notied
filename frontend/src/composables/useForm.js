import { reactive, computed } from 'vue'

// Minimal, dependency-free form validation.
// Usage:
//   const form = useForm(
//     { email: '', password: '' },
//     { email: [required(), email()], password: [required(), minLen(8)] }
//   )
//   form.fields.email, form.errors.email, form.touch('email'), form.validate()

export function required(msg = 'This field is required.') {
  return (v) => (v != null && String(v).trim() !== '' ? null : msg)
}
export function email(msg = 'Enter a valid email address.') {
  return (v) => (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v).trim()) ? null : msg)
}
export function minLen(n, msg) {
  return (v) => (String(v).length >= n ? null : msg || `Must be at least ${n} characters.`)
}
export function maxLen(n, msg) {
  return (v) => (String(v).length <= n ? null : msg || `Must be ${n} characters or fewer.`)
}
export function matches(getOther, msg = 'Values do not match.') {
  return (v) => (v === getOther() ? null : msg)
}

export function useForm(initial, rules) {
  const fields = reactive({ ...initial })
  const errors = reactive(
    Object.keys(initial).reduce((acc, k) => ((acc[k] = null), acc), {})
  )
  const touched = reactive(
    Object.keys(initial).reduce((acc, k) => ((acc[k] = false), acc), {})
  )

  function validateField(name) {
    const fieldRules = rules[name] || []
    for (const rule of fieldRules) {
      const err = rule(fields[name], fields)
      if (err) {
        errors[name] = err
        return false
      }
    }
    errors[name] = null
    return true
  }

  function touch(name) {
    touched[name] = true
    validateField(name)
  }

  function validate() {
    let ok = true
    for (const name of Object.keys(rules)) {
      touched[name] = true
      if (!validateField(name)) ok = false
    }
    return ok
  }

  function reset() {
    Object.keys(initial).forEach((k) => {
      fields[k] = initial[k]
      errors[k] = null
      touched[k] = false
    })
  }

  function setServerErrors(serverErrors = {}) {
    Object.entries(serverErrors).forEach(([k, v]) => {
      if (k in errors) {
        errors[k] = Array.isArray(v) ? v[0] : v
        touched[k] = true
      }
    })
  }

  const isValid = computed(() => Object.values(errors).every((e) => !e))

  return { fields, errors, touched, touch, validate, reset, validateField, setServerErrors, isValid }
}
