<div
    class="fcw-feed flex flex-col h-[600px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
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
    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-0.5">
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
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
                'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-400' => $this->conversation->status->value === 'open',
                'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400' => $this->conversation->status->value === 'pending',
                'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400' => $this->conversation->status->value === 'closed',
            ])>
                {{ $this->conversation->status->getLabel() }}
            </span>
        @endif
    </div>

    <div x-ref="feed" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-950">
        @forelse ($this->messages as $message)
            @php
                $value = $message->sender_type?->value ?? (string) $message->sender_type;
                $isVisitor = $value === 'visitor';
                $isSystem = $value === 'system';
            @endphp

            @if ($isSystem)
                <div class="flex justify-center">
                    <div class="rounded-full bg-gray-200 px-3 py-1 text-xs italic text-gray-600 dark:bg-gray-700 dark:text-gray-300">
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
                        'bg-white text-gray-900 rounded-bl-sm border border-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700' => $isVisitor,
                        'bg-primary-500 text-white rounded-br-sm' => ! $isVisitor,
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

    <form wire:submit="sendMessage" class="flex gap-2 border-t border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
        <textarea
            wire:model="newMessage"
            x-on:keydown.enter.prevent="if (! $event.shiftKey) { $wire.sendMessage(); }"
            rows="2"
            placeholder="{{ __('filament-chat-widget::chat.feed.placeholder') }}"
            class="flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
        ></textarea>
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="shrink-0 rounded-lg bg-primary-500 px-4 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-primary-600 dark:hover:bg-primary-500"
        >
            {{ __('filament-chat-widget::chat.feed.send') }}
        </button>
    </form>
</div>
