<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Filament\Resources\ChatWidgets\Pages;

use Cegem360\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Cegem360\FilamentChatWidget\Filament\Resources\ChatWidgets\ChatWidgetResource;
use Cegem360\FilamentChatWidget\Models\ChatWidget;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Override;

class EditChatWidget extends EditRecord
{
    protected static string $resource = ChatWidgetResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('embedCode')
                ->label(__('filament-chat-widget::chat.actions.embed_code'))
                ->icon(Heroicon::CodeBracket)
                ->modalHeading(__('filament-chat-widget::chat.actions.embed_code'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament-chat-widget::chat.actions.close'))
                ->schema([
                    Textarea::make('direct_link')
                        ->label(__('filament-chat-widget::chat.embed.direct_link'))
                        ->readOnly()
                        ->rows(2)
                        ->default(fn (ChatWidget $record): string => $this->buildDirectLink($record))
                        ->columnSpanFull()
                        ->helperText(__('filament-chat-widget::chat.embed.direct_link_help')),
                    Textarea::make('script_snippet')
                        ->label(__('filament-chat-widget::chat.embed.script_snippet'))
                        ->readOnly()
                        ->rows(3)
                        ->default(fn (ChatWidget $record): string => $this->buildScriptSnippet($record))
                        ->columnSpanFull()
                        ->helperText(__('filament-chat-widget::chat.embed.script_snippet_help')),
                ]),
            DeleteAction::make(),
        ];
    }

    private function buildDirectLink(ChatWidget $record): string
    {
        $prefix = (string) config('filament-chat-widget.routes.prefix', 'chat');

        return url('/' . trim($prefix, '/') . '/widget/' . $this->resolveSlug($record));
    }

    private function buildScriptSnippet(ChatWidget $record): string
    {
        $src = url((string) config('filament-chat-widget.widget_script_path', '/vendor/filament-chat-widget/chat-widget.js'));

        return '<script src="' . $src . '" data-team="' . $this->resolveSlug($record) . '" async></script>';
    }

    private function resolveSlug(ChatWidget $record): string
    {
        $tenantForeignKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');
        $tenantKey = $record->getAttribute($tenantForeignKey);

        if ($tenantKey === null) {
            return '';
        }

        $resolver = app(ChatWidgetTenantResolver::class);

        return (string) ($resolver->resolveSlugByTenantKey($tenantKey) ?? '');
    }
}
