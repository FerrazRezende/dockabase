<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Upload, Trash2, Loader2, X } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription
} from '@/components/ui/dialog'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'

interface Props {
  open?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  open: false
})

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const preview = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const isUploading = ref(false)

const uploadForm = useForm({
  photo: null as File | null
})

const deleteForm = useForm({})

const selectFile = () => {
  fileInput.value?.click()
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    processFile(file)
  }
}

const processFile = (file: File) => {
  // Validate file type
  if (!file.type.startsWith('image/')) {
    uploadForm.setError('photo', __('Please select a valid image file.'))
    return
  }

  // Validate file size (2MB)
  const maxSize = 2 * 1024 * 1024 // 2MB in bytes
  if (file.size > maxSize) {
    uploadForm.setError('photo', __('Image size must be less than 2MB.'))
    return
  }

  uploadForm.clearErrors('photo')

  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    preview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)

  // Upload file
  uploadForm.photo = file
  isUploading.value = true

  uploadForm.post(route('profile.photo.store'), {
    onSuccess: () => {
      window.location.reload()
    },
    onError: () => {
      isUploading.value = false
    }
  })
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false

  const file = event.dataTransfer?.files[0]
  if (file) {
    processFile(file)
  }
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = true
}

const handleDragLeave = () => {
  isDragging.value = false
}

const deletePhoto = () => {
  if (confirm(__('Are you sure you want to remove your profile photo?'))) {
    deleteForm.delete(route('profile.photo.destroy'), {
      onSuccess: () => {
        window.location.reload()
      }
    })
  }
}
</script>

<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-2xl">
      <button
        @click="isOpen = false"
        class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground"
      >
        <X class="h-4 w-4" />
      </button>

      <DialogHeader>
        <DialogTitle>{{ __('Profile Photo') }}</DialogTitle>
        <DialogDescription>
          {{ __('View, upload, or change your profile photo.') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6">
        <!-- Avatar Preview -->
        <div class="flex justify-center">
          <div
            class="h-64 w-64 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800"
          >
            <img
              v-if="preview || $page.props.auth.user.avatar"
              :src="preview || $page.props.auth.user.avatar"
              :alt="$page.props.auth.user.name"
              class="h-full w-full object-cover"
            />
            <div
              v-else
              class="h-full w-full flex items-center justify-center text-gray-400"
            >
              <Upload class="h-16 w-16" />
            </div>
          </div>
        </div>

        <!-- Upload Area -->
        <div
          @click="selectFile"
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
          :class="[
            'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
            isDragging
              ? 'border-primary bg-primary/5'
              : 'border-gray-300 dark:border-gray-700 hover:border-primary dark:hover:border-primary'
          ]"
        >
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/jpg"
            class="hidden"
            @change="handleFileChange"
          />

          <div v-if="isUploading" class="flex flex-col items-center gap-2">
            <Loader2 class="h-8 w-8 animate-spin text-primary" />
            <p class="text-sm text-muted-foreground">{{ __('Uploading...') }}</p>
          </div>

          <div v-else class="space-y-2">
            <Upload class="h-8 w-8 mx-auto text-muted-foreground" />
            <p class="text-sm font-medium">{{ __('Click to upload or drag and drop') }}</p>
            <p class="text-xs text-muted-foreground">{{ __('JPG, PNG up to 2MB') }}</p>
          </div>
        </div>

        <!-- Upload Error -->
        <Alert
          v-if="uploadForm.errors.photo"
          variant="destructive"
        >
          {{ uploadForm.errors.photo }}
        </Alert>

        <!-- Delete Button -->
        <div v-if="$page.props.auth.user.avatar" class="flex justify-center">
          <Button
            variant="destructive"
            @click="deletePhoto"
            :disabled="deleteForm.processing"
          >
            <Trash2 class="h-4 w-4 mr-2" />
            {{ __('Remove photo') }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
