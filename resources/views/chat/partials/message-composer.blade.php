<div class="px-4 py-3 bg-white border-t border-slate-200 shrink-0 relative" 
     x-data="{ 
        attachMenuOpen: false, 
        selectedFile: null, 
        isSubmitting: false,
        fileCategory: null,
        triggerUpload(type) {
            this.attachMenuOpen = false;
            // reset all inputs first
            ['image', 'video', 'pdf', 'word', 'ppt', 'excel'].forEach(t => {
                const el = document.getElementById('attach-' + t + '-{{ $activeConversation->id }}');
                if(el) el.value = '';
            });
            document.getElementById('attach-' + type + '-{{ $activeConversation->id }}').click();
        },
        handleFileSelect(event, type) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file;
                this.fileCategory = type;
            }
        },
        removeFile() {
            this.selectedFile = null;
            this.fileCategory = null;
            ['image', 'video', 'pdf', 'word', 'ppt', 'excel'].forEach(t => {
                const el = document.getElementById('attach-' + t + '-{{ $activeConversation->id }}');
                if(el) el.value = '';
            });
        }
     }">
     
    <!-- Attachment Preview (Above Input) -->
    <div x-show="selectedFile" x-cloak class="mb-3 p-3 bg-slate-50 border border-slate-200 rounded-xl flex flex-col gap-2 relative">
        <button type="button" @click="removeFile()" class="absolute top-2 right-2 p-1 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors z-10 bg-white shadow-sm border border-slate-100">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <div class="flex items-center gap-3">
            <!-- Icon based on type -->
            <div class="shrink-0 size-10 flex items-center justify-center rounded-lg bg-white border border-slate-200 shadow-sm">
                <template x-if="fileCategory === 'image'"><svg class="size-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></template>
                <template x-if="fileCategory === 'video'"><svg class="size-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></template>
                <template x-if="fileCategory === 'pdf'"><svg class="size-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></template>
                <template x-if="fileCategory === 'word'"><svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></template>
                <template x-if="fileCategory === 'ppt'"><svg class="size-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></template>
                <template x-if="fileCategory === 'excel'"><svg class="size-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></template>
            </div>
            <div class="flex-1 min-w-0 pr-8">
                <div class="text-sm font-semibold text-slate-700 truncate" x-text="selectedFile ? selectedFile.name : ''"></div>
                <div class="text-xs text-slate-500 mt-0.5" x-text="selectedFile ? (selectedFile.size / 1024 / 1024).toFixed(2) + ' MB' : ''"></div>
            </div>
        </div>
    </div>

     <!-- Upload Errors -->
    @error('attachment')
        <div class="mb-2 text-sm text-red-600 font-medium px-2">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('chat.messages.store', $activeConversation) }}" enctype="multipart/form-data" class="flex items-end gap-2" 
          @submit.prevent="
              if (isSubmitting) return;
              const body = $event.target.querySelector('textarea[name=body]')?.value?.trim() ?? '';
              if (!selectedFile && !body) return;
              isSubmitting = true;

              // Build FormData manually so empty file inputs don't interfere
              const fd = new FormData();
              fd.append('_token', document.querySelector('meta[name=csrf-token]')?.content ?? '{{ csrf_token() }}');
              if (body) fd.append('body', body);
              if (selectedFile) fd.append('attachment', selectedFile, selectedFile.name);

              axios.post($event.target.action, fd, {
                  headers: { 
                      'Content-Type': 'multipart/form-data',
                      'Accept': 'application/json',
                      'X-Requested-With': 'XMLHttpRequest'
                  }
              }).then(response => {
                  $event.target.reset();
                  removeFile();
                  $event.target.querySelector('textarea').style.height = '';
                  window.dispatchEvent(new CustomEvent('message-sent', { detail: response.data }));
              }).catch(error => {
                  console.error(error);
                  if (error.response && error.response.data && error.response.data.errors) {
                      alert(Object.values(error.response.data.errors).flat().join('\n'));
                  } else if (error.response && error.response.data && error.response.data.message) {
                      alert(error.response.data.message);
                  } else {
                      alert('Erreur lors de l\'envoi du message');
                  }
              }).finally(() => {
                  isSubmitting = false;
              });
          ">
        @csrf
        
        <!-- Hidden File Inputs -->
        <input type="file" name="attachment" id="attach-image-{{ $activeConversation->id }}" class="hidden" accept="image/*" @change="handleFileSelect($event, 'image')">
        <input type="file" name="attachment" id="attach-video-{{ $activeConversation->id }}" class="hidden" accept="video/mp4,video/webm,video/quicktime" @change="handleFileSelect($event, 'video')">
        <input type="file" name="attachment" id="attach-pdf-{{ $activeConversation->id }}" class="hidden" accept=".pdf" @change="handleFileSelect($event, 'pdf')">
        <input type="file" name="attachment" id="attach-word-{{ $activeConversation->id }}" class="hidden" accept=".doc,.docx" @change="handleFileSelect($event, 'word')">
        <input type="file" name="attachment" id="attach-ppt-{{ $activeConversation->id }}" class="hidden" accept=".ppt,.pptx" @change="handleFileSelect($event, 'ppt')">
        <input type="file" name="attachment" id="attach-excel-{{ $activeConversation->id }}" class="hidden" accept=".xls,.xlsx,.csv" @change="handleFileSelect($event, 'excel')">
               
        <!-- Attachment Menu -->
        <div class="relative shrink-0">
            <button type="button" @click="attachMenuOpen = !attachMenuOpen" @click.outside="attachMenuOpen = false"
                    class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                    :class="attachMenuOpen ? 'bg-blue-50 text-blue-600' : ''"
                    title="Joindre un fichier">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </button>
            
            <!-- Dropdown -->
            <div x-show="attachMenuOpen" x-transition.opacity.duration.200ms x-cloak 
                 class="absolute bottom-12 left-0 mb-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                
                @if(\App\Models\Setting::get('enable_image_attachments', true))
                <button type="button" @click="triggerUpload('image')" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left">
                    <div class="size-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="font-medium text-slate-700 text-sm">Image</span>
                </button>
                @endif
                
                @if(\App\Models\Setting::get('enable_video_attachments', true))
                <button type="button" @click="triggerUpload('video')" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left">
                    <div class="size-8 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="font-medium text-slate-700 text-sm">Vidéo</span>
                </button>
                @endif

                @if(\App\Models\Setting::get('enable_pdf_attachments', true))
                <button type="button" @click="triggerUpload('pdf')" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left">
                    <div class="size-8 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="font-medium text-slate-700 text-sm">PDF</span>
                </button>
                @endif

                @if(\App\Models\Setting::get('enable_word_attachments', true))
                <button type="button" @click="triggerUpload('word')" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left">
                    <div class="size-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-700">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="font-medium text-slate-700 text-sm">Word</span>
                </button>
                @endif

                @if(\App\Models\Setting::get('enable_powerpoint_attachments', true))
                <button type="button" @click="triggerUpload('ppt')" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left">
                    <div class="size-8 rounded-full bg-orange-50 flex items-center justify-center text-orange-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="font-medium text-slate-700 text-sm">PowerPoint</span>
                </button>
                @endif

                @if(\App\Models\Setting::get('enable_excel_attachments', true))
                <button type="button" @click="triggerUpload('excel')" class="w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left">
                    <div class="size-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <span class="font-medium text-slate-700 text-sm">Excel</span>
                </button>
                @endif
                
            </div>
        </div>
        
        <!-- Text Input -->
        <div class="flex-1 relative">
            <textarea name="body" rows="1" 
                      placeholder="Écrivez un message ou une légende..." 
                      class="block w-full resize-none py-2.5 pl-4 pr-12 bg-slate-50 border border-slate-200 rounded-full text-[15px] focus:bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-colors custom-scrollbar"
                      oninput="this.style.height = ''; this.style.height = Math.min(this.scrollHeight, 120) + 'px'"
                      @keydown.enter.prevent="if(!$event.shiftKey) { $el.closest('form').dispatchEvent(new Event('submit', {cancelable: true, bubbles: true})); }"
                      :disabled="isSubmitting"></textarea>
                      
            <!-- Send Button -->
            <button type="submit" 
                    class="absolute right-1.5 bottom-1.5 p-1.5 text-white bg-blue-600 hover:bg-blue-700 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                    :disabled="isSubmitting">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
    </form>
</div>
