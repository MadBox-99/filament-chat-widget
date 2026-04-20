<div
    class="fcw-feed flex h-[600px] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
    wire:poll.3s="poll"
    x-data="{
        scrollBottom() {
            this.$nextTick(() => {
                const el = this.$refs.feed;
                if (el) { el.scrollTop = el.scrollHeight; }
            });
        },
    }"
    x-init="scrollBottom()"
    x-on:chat-feed-scroll-bottom.window="scrollBottom()"
>
    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-0.5">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ $this->conversation?->visitor_name ?: __('filament-chat-widget::chat.feed.anonymous') }}
            </span>
            @if ($this->conversation?->visitor_email)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $this->conversation->visitor_email }}
                </span>
            @endif
        </div>
        @if ($this->conversation?->status)
            <span @class([
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                'bg-success-50 text-success-700 ring-1 ring-success-600/20 dark:bg-success-400/10 dark:text-success-300 dark:ring-success-400/20' => $this->conversation->status->value === 'open',
                'bg-warning-50 text-warning-700 ring-1 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/20' => $this->conversation->status->value === 'pending',
                'bg-gray-50 text-gray-700 ring-1 ring-gray-600/20 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10' => $this->conversation->status->value === 'closed',
            ])>
                {{ $this->conversation->status->getLabel() }}
            </span>
        @endif
    </div>

    <div x-ref="feed" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-900">
        @forelse ($this->messages as $message)
            @php
                $value = $message->sender_type?->value ?? (string) $message->sender_type;
                $isVisitor = $value === 'visitor';
                $isSystem = $value === 'system';
            @endphp

            @if ($isSystem)
                <div class="flex justify-center">
                    <div class="rounded-full bg-white px-3 py-1 text-xs italic text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                        {{ $message->message }}
                    </div>
                </div>
            @else
                <div @class([
                    'flex gap-2',
                    'justify-start' => $isVisitor,
                    'justify-end' => ! $isVisitor,
                ])>
                    <div @class([
                        'max-w-[75%] space-y-1 rounded-2xl px-4 py-2 text-sm shadow-sm',
                        'rounded-bl-sm bg-white text-gray-900 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-100 dark:ring-white/10' => $isVisitor,
                        'rounded-br-sm bg-primary-600 text-white dark:bg-primary-500' => ! $isVisitor,
                    ])>
                        <div class="whitespace-pre-wrap break-words">{{ $message->message }}</div>
                        <div @class([
                            'text-[10px] opacity-70',
                            'text-right' => ! $isVisitor,
                        ])>
                            {{ $message->created_at?->format('H:i') }}
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="flex h-full items-center justify-center">
                <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('filament-chat-widget::chat.feed.empty') }}
                </div>
            </div>
        @endforelse
    </div>

    <form wire:submit="sendMessage" class="flex gap-2 border-t border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
        <textarea
            wire:model="newMessage"
            x-on:keydown.enter.prevent="if (! $event.shiftKey) { $wire.sendMessage(); }"
            rows="2"
            placeholder="{{ __('filament-chat-widget::chat.feed.placeholder') }}"
            class="flex-1 resize-none rounded-lg border-0 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm ring-1 ring-inset ring-gray-300 transition focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-white/5 dark:text-white dark:placeholder-gray-500 dark:ring-white/10 dark:focus:ring-primary-400"
        ></textarea>
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="shrink-0 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-primary-500 dark:hover:bg-primary-400"
        >
            {{ __('filament-chat-widget::chat.feed.send') }}
        </button>
    </form>
</div>
