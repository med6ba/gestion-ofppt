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
                        @if($message->attachment_type === 'image')
                            <a href="{{ $message->attachmentUrl() }}" target="_blank" class="block">
                                <img src="{{ $message->attachmentUrl() }}" alt="Image" class="max-h-60 rounded-xl object-cover border {{ $isOwn ? 'border-blue-500/50' : 'border-slate-200' }}">
                            </a>
                        @else
                            <a href="{{ $message->attachmentUrl() }}" target="_blank" 
                               class="flex items-center gap-3 p-3 rounded-xl border transition-colors
                                      {{ $isOwn ? 'bg-blue-700/50 border-blue-500 hover:bg-blue-700' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }}">
                                <svg class="size-8 {{ $isOwn ? 'text-blue-200' : 'text-red-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01"/></svg>
                                <div class="overflow-hidden">
                                    <p class="text-sm font-medium truncate {{ $isOwn ? 'text-white' : 'text-slate-700' }}" title="{{ $message->attachment_original_name }}">
                                        {{ $message->attachment_original_name }}
                                    </p>
                                    <p class="text-[11px] {{ $isOwn ? 'text-blue-200' : 'text-slate-500' }}">Document PDF</p>
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
