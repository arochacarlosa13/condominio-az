<template>
  <th
    :class="[
      'cursor-pointer select-none transition-colors duration-150 py-2 px-2.5 text-xs',
      align === 'right' ? 'text-right' : (align === 'center' ? 'text-center' : 'text-left'),
      isActive ? 'bg-slate-100 text-primary font-weight-bold' : 'hover:bg-slate-100/80 text-slate-700 font-weight-medium',
      customClass
    ]"
    :style="headerStyle"
    :title="`Ordenar por ${label || 'esta columna'}`"
    @click="onClick"
  >
    <div
      :class="[
        'd-inline-flex align-center gap-1',
        align === 'right' ? 'justify-end' : (align === 'center' ? 'justify-center' : 'justify-start')
      ]"
      style="max-width: 100%;"
    >
      <span class="leading-tight">
        <slot>{{ label }}</slot>
      </span>
      <v-icon
        :icon="iconName"
        size="13"
        :color="isActive ? 'primary' : 'slate-400'"
        class="flex-shrink-0"
        :style="{ opacity: isActive ? 1 : 0.4 }"
      />
    </div>
  </th>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, default: '' },
  colKey: { type: String, required: true },
  sortBy: { type: [String, null], default: null },
  sortDesc: { type: Boolean, default: false },
  align: { type: String, default: 'left' }, // 'left' | 'right' | 'center'
  width: { type: String, default: '' },
  customClass: { type: String, default: '' },
});

const emit = defineEmits(['sort']);

const isActive = computed(() => props.sortBy === props.colKey);

const iconName = computed(() => {
  if (!isActive.value) return 'mdi-swap-vertical';
  return props.sortDesc ? 'mdi-arrow-down' : 'mdi-arrow-up';
});

const headerStyle = computed(() => {
  const style = {};
  if (props.width) {
    style.width = props.width;
    style.minWidth = props.width;
  }
  return style;
});

const onClick = () => {
  emit('sort', props.colKey);
};
</script>

