@php
    $isOwn = $message->sender_id === auth()->id();
@endphp

<div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }} group">
    <div class="flex max-w-[85%] sm:max-w-[75%] {{ $isOwn ? 'flex-row-reverse' : 'flex-row' }} items-end gap-2">
        
        <!-- Avatar (only for received messages in group chats, or if we want it everywhere) -->
        @if(!$isOwn && $conversation->type === 'group')
            <div class="shrink-0 mb-5">
                <x-user-avatar :user="$message->sender" size="sm" />
            </div>
        @endif
        
        <div class="flex flex-col {{ $isOwn ? 'items-end' : 'items-start' }}">
            <!-- Sender name for group chats -->
            @if(!$isOwn && $conversation->type === 'group')
                <span class="text-[11px] text-slate-500 mb-1 ml-1">{{ $message->sender->name }}</span>
            @endif

            <!-- Bubble -->
            <div class="relative px-4 py-2.5 rounded-2xl shadow-sm text-[15px]
                {{ $isOwn 
                    ? 'bg-blue-600 text-white rounded-br-sm' 
                    : 'bg-white text-slate-800 border border-slate-100 rounded-bl-sm' }}">
                
                @if($message->body)
                    <p class="whitespace-pre-wrap break-words leading-relaxed">{!! nl2br(e($message->body)) !!}</p>
                @endif
                
                @if($message->attachment_path)
                    <div class="mt-2 mb-1">
                        @if($message->attachment_category === 'image')
                            <a href="{{ $message->attachmentUrl() }}" target="_blank" class="block">
                                <img src="{{ $message->attachmentUrl() }}" alt="Image" class="max-h-60 rounded-xl object-cover border {{ $isOwn ? 'border-blue-500/50' : 'border-slate-200' }}">
                            </a>
                        @elseif($message->attachment_category === 'video')
                            <video controls class="max-h-60 rounded-xl w-full border {{ $isOwn ? 'border-blue-500/50' : 'border-slate-200' }}">
                                <source src="{{ $message->attachmentUrl() }}" type="{{ $message->attachment_mime_type ?? 'video/mp4' }}">
                                Votre navigateur ne supporte pas la lecture de vidéos.
                            </video>
                        @else
                            @php
                                $iconColor = match($message->attachment_category) {
                                    'pdf' => 'text-red-500',
                                    'word' => 'text-blue-600',
                                    'ppt' => 'text-orange-500',
                                    'excel' => 'text-emerald-500',
                                    default => 'text-slate-500'
                                };
                                $categoryName = match($message->attachment_category) {
                                    'pdf' => 'PDF',
                                    'word' => 'Word',
                                    'ppt' => 'PowerPoint',
                                    'excel' => 'Excel',
                                    default => 'Document'
                                };
                            @endphp
                            <a href="{{ $message->attachmentUrl() }}" target="_blank" 
                               class="flex items-center gap-3 p-3 rounded-xl border transition-colors
                                      {{ $isOwn ? 'bg-blue-700/50 border-blue-500 hover:bg-blue-700' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }}">
                                <div class="shrink-0 size-10 flex items-center justify-center rounded-lg {{ $isOwn ? 'bg-blue-600' : 'bg-white' }} shadow-sm">
                                    @if($message->attachment_category === 'pdf')
                                        <svg class="size-6 {{ $isOwn ? 'text-blue-100' : $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @elseif($message->attachment_category === 'word')
                                        <svg class="size-6 {{ $isOwn ? 'text-blue-100' : $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @elseif($message->attachment_category === 'ppt')
                                        <svg class="size-6 {{ $isOwn ? 'text-blue-100' : $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @elseif($message->attachment_category === 'excel')
                                        <svg class="size-6 {{ $isOwn ? 'text-blue-100' : $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @else
                                        <svg class="size-6 {{ $isOwn ? 'text-blue-100' : $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    @endif
                                </div>
                                <div class="overflow-hidden flex-1">
                                    <p class="text-sm font-medium truncate {{ $isOwn ? 'text-white' : 'text-slate-700' }}" title="{{ $message->attachment_original_name }}">
                                        {{ $message->attachment_original_name }}
                                    </p>
                                    <div class="flex items-center justify-between mt-0.5">
                                        <p class="text-[11px] {{ $isOwn ? 'text-blue-200' : 'text-slate-500' }}">{{ $message->attachment_size ? number_format($message->attachment_size / 1024 / 1024, 2) . ' MB • ' : '' }}{{ $categoryName }}</p>
                                        <svg class="size-4 {{ $isOwn ? 'text-blue-200' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </div>
                                </div>
                            </a>
                        @endif
                    </div>
                @endif
                
                <!-- Timestamp & Status -->
                <div class="flex items-center justify-end gap-1 mt-1 -mb-1">
                    <span class="text-[10px] {{ $isOwn ? 'text-blue-200' : 'text-slate-400' }}">
                        {{ $message->created_at->format('H:i') }}
                    </span>
                    @if($isOwn)
                        <span class="{{ $message->is_read ? 'text-blue-200' : 'text-blue-400' }}">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                                @if($message->is_read)
                                    <!-- Double check for read -->
                                    <polyline points="15 6 9 12" class="opacity-50"></polyline>
                                @endif
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
    </div>
</div>
