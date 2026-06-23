// Sticky-note color palette — keys map to Tailwind `sticky-*` tokens and to the
// `color` column in the notes table. Hex values are duplicated here so canvas
// inline styles can use them directly.

export const NOTE_COLORS = [
  { key: 'yellow', hex: '#FFE89C', ring: '#E9CF6E' },
  { key: 'green', hex: '#C9EDC1', ring: '#A6D89A' },
  { key: 'blue', hex: '#BFE0FB', ring: '#92C6EE' },
  { key: 'pink', hex: '#FFC7D3', ring: '#F2A0B3' },
  { key: 'purple', hex: '#D9CDF7', ring: '#BBA8E8' },
  { key: 'orange', hex: '#FFD3A8', ring: '#F0B27E' },
]

export function colorHex(key) {
  return (NOTE_COLORS.find((c) => c.key === key) || NOTE_COLORS[0]).hex
}
export function colorRing(key) {
  return (NOTE_COLORS.find((c) => c.key === key) || NOTE_COLORS[0]).ring
}

export function useNoteColors() {
  return { NOTE_COLORS, colorHex, colorRing }
}
