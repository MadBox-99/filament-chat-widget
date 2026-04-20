<x-filament-panels::page>
    <livewire:filament-chat-widget.chat-conversation-feed
        :conversation-id="$this->record->getKey()"
        wire:key="fcw-chat-feed-{{ $this->record->getKey() }}"
    />
</x-filament-panels::page>
