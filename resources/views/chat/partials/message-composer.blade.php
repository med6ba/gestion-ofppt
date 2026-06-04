<div class="px-4 py-3 bg-white border-t border-slate-200 shrink-0">
    <!-- Preview selected file (Alpine) -->
    <div x-show="selectedFile" x-cloak class="mb-3 p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-3 overflow-hidden">
            <svg class="size-6 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span class="text-sm font-medium text-slate-700 truncate" x-text="selectedFile ? selectedFile.name : ''"></span>
        </div>
        <button type="button" @click="selectedFile = null; $refs.fileInput.value = ''" class="p-1 text-slate-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Upload Errors -->
    @error('attachment')
        <div class="mb-2 text-sm text-red-600 font-medium px-2">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('chat.messages.store', $activeConversation) }}" enctype="multipart/form-data" class="flex items-end gap-2" 
          x-data="{ isSubmitting: false }" 
          @submit="isSubmitting = true"
          @message-sent.window="isSubmitting = false; selectedFile = null">
        @csrf
        
        <!-- File Input (Hidden) -->
        <input type="file" name="attachment" id="attachment-{{ $activeConversation->id }}" class="hidden" 
               accept=".jpg,.jpeg,.png,.webp,.pdf"
               x-ref="fileInput" 
               @change="selectedFile = $event.target.files[0]">
               
        <!-- Attachment Button -->
        <button type="button" @click="$refs.fileInput.click()" 
                class="shrink-0 p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                title="Joindre un fichier">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        </button>
        
        <!-- Text Input -->
        <div class="flex-1 relative">
            <textarea name="body" rows="1" 
                      placeholder="Écrivez un message..." 
                      class="block w-full resize-none py-2.5 pl-4 pr-12 bg-slate-50 border border-slate-200 rounded-full text-[15px] focus:bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-colors custom-scrollbar"
                      oninput="this.style.height = ''; this.style.height = Math.min(this.scrollHeight, 120) + 'px'"
                      @keydown.enter.prevent="if(!$event.shiftKey) { $el.closest('form').submit(); isSubmitting = true; }"
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
