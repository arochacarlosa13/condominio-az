<template>
  <div class="pa-3 px-sm-4 d-flex flex-column flex-sm-row justify-space-between align-center gap-3 border-t border-slate-100 bg-slate-50/50">
    <div class="text-caption text-slate-600 text-center text-sm-left">
      <template v-if="totalItems > 0">
        Mostrando <span class="font-weight-bold text-slate-800">{{ startIndex }}</span> a <span class="font-weight-bold text-slate-800">{{ endIndex }}</span> de <span class="font-weight-bold text-slate-800">{{ totalItems }}</span> registros
        <span v-if="search && totalItems !== originalTotal" class="text-slate-400 ms-1 font-italic d-block d-sm-inline">
          (filtrado de {{ originalTotal }} totales)
        </span>
      </template>
      <template v-else>
        No hay registros para mostrar
      </template>
    </div>

    <div v-if="totalPages > 1" class="d-flex align-center justify-center ms-sm-auto w-100 w-sm-auto">
      <v-pagination
        :model-value="currentPage"
        @update:model-value="$emit('update:currentPage', $event)"
        :length="totalPages"
        :total-visible="4"
        density="compact"
        rounded="circle"
        active-color="primary"
        size="small"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({
  currentPage: { type: Number, default: 1 },
  totalPages: { type: Number, default: 1 },
  totalItems: { type: Number, default: 0 },
  originalTotal: { type: Number, default: 0 },
  startIndex: { type: Number, default: 0 },
  endIndex: { type: Number, default: 0 },
  search: { type: String, default: '' },
});

defineEmits(['update:currentPage']);
</script>

