<template>
  <div class="pa-3 px-sm-4 d-flex flex-column flex-md-row justify-space-between align-stretch align-md-center gap-3 border-b border-slate-100 bg-slate-50/50">
    <!-- Top/Right on mobile: Custom Action Filters & Search Box -->
    <div class="d-flex flex-column flex-sm-row align-stretch align-sm-center flex-wrap gap-2 order-1 order-md-2 ms-md-auto w-100 w-md-auto">
      <div class="d-flex align-center flex-wrap gap-2 flex-grow-1 flex-sm-grow-0">
        <slot name="actions"></slot>
      </div>

      <v-text-field
        :model-value="search"
        @update:model-value="$emit('update:search', $event || '')"
        :placeholder="placeholder"
        prepend-inner-icon="mdi-magnify"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        class="bg-white rounded w-100"
        style="min-width: 200px; max-width: 100%;"
      />
    </div>

    <!-- Bottom/Left on mobile: Per Page Selector -->
    <div class="d-flex align-center justify-space-between justify-sm-start gap-2 order-2 order-md-1">
      <div class="d-flex align-center gap-2">
        <span class="text-caption text-slate-500 font-weight-medium">Mostrar</span>
        <v-select
          :model-value="perPage"
          @update:model-value="$emit('update:perPage', Number($event))"
          :items="perPageOptions"
          density="compact"
          variant="outlined"
          hide-details
          style="width: 80px;"
          class="bg-white rounded"
        />
        <span class="text-caption text-slate-500 font-weight-medium">registros</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  search: { type: String, default: '' },
  perPage: { type: Number, default: 10 },
  perPageOptions: { type: Array, default: () => [5, 10, 25, 50, 100] },
  placeholder: { type: String, default: 'Buscar registros...' },
});

defineEmits(['update:search', 'update:perPage']);
</script>

