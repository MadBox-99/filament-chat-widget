<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Http\Controllers;

use Cegem360\FilamentChatWidget\Contracts\ChatWidgetTenantResolver;
use Cegem360\FilamentChatWidget\Enums\ChatConversationStatus;
use Cegem360\FilamentChatWidget\Enums\ChatSenderType;
use Cegem360\FilamentChatWidget\Models\ChatConversation;
use Cegem360\FilamentChatWidget\Models\ChatMessage;
use Cegem360\FilamentChatWidget\Models\ChatWidget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ChatWidgetController extends Controller
{
    public function __construct(
        private readonly ChatWidgetTenantResolver $tenantResolver,
    ) {}

    /**
     * Return the public widget configuration for a given tenant slug.
     */
    public function config(string $slug): JsonResponse
    {
        $widget = $this->findActiveWidgetForSlug($slug);

        return new JsonResponse([
            'title' => $widget->title,
            'welcome_message' => $widget->welcome_message,
            'color' => $widget->color,
            'position' => $widget->position,
            'offline_message' => $widget->offline_message,
            'auto_reply_message' => $widget->auto_reply_message,
        ]);
    }

    /**
     * Start a new chat conversation for the given tenant slug.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'visitor_name' => ['nullable', 'string', 'max:255'],
            'visitor_email' => ['nullable', 'email', 'max:255'],
        ]);

        $widget = $this->findActiveWidgetForSlug($validated['slug']);
        $tenantForeignKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        $conversation = ChatConversation::query()->create([
            $tenantForeignKey => $widget->getAttribute($tenantForeignKey),
            'visitor_name' => $validated['visitor_name'] ?? null,
            'visitor_email' => $validated['visitor_email'] ?? null,
            'visitor_ip' => $request->ip(),
            'status' => ChatConversationStatus::Open,
            'started_at' => now(),
            'last_message_at' => now(),
            'unread_count' => 0,
        ]);

        if (! empty($widget->auto_reply_message)) {
            ChatMessage::query()->create([
                'chat_conversation_id' => $conversation->id,
                'sender_type' => ChatSenderType::System,
                'sender_id' => null,
                'message' => $widget->auto_reply_message,
            ]);

            $conversation->update(['last_message_at' => now()]);
        }

        return new JsonResponse([
            'uuid' => $conversation->uuid,
            'messages' => $this->messagesPayload($conversation),
        ], 201);
    }

    /**
     * Return messages for the given conversation, optionally filtered by the `since` parameter.
     */
    public function messages(Request $request, string $uuid): JsonResponse
    {
        $conversation = $this->findConversationByUuid($uuid);

        $since = (int) $request->query('since', '0');

        $messages = $conversation->messages()
            ->when($since > 0, fn ($query) => $query->where('id', '>', $since))
            ->orderBy('id')
            ->get();

        return new JsonResponse([
            'messages' => $messages->map(fn (ChatMessage $message): array => $this->messageToArray($message))->all(),
        ]);
    }

    /**
     * Store a visitor message in the given conversation.
     */
    public function sendMessage(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $this->findConversationByUuid($uuid);

        /** @var ChatMessage $message */
        $message = ChatMessage::query()->create([
            'chat_conversation_id' => $conversation->id,
            'sender_type' => ChatSenderType::Visitor,
            'sender_id' => null,
            'message' => $validated['message'],
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => $conversation->unread_count + 1,
        ]);

        return new JsonResponse([
            'message' => $this->messageToArray($message),
        ], 201);
    }

    /**
     * Find an active chat widget by the owning tenant's slug.
     */
    private function findActiveWidgetForSlug(string $slug): ChatWidget
    {
        $tenantKey = $this->tenantResolver->resolveTenantKeyBySlug($slug);

        if ($tenantKey === null) {
            throw new NotFoundHttpException();
        }

        $tenantForeignKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        $widget = ChatWidget::withoutGlobalScopes()
            ->where($tenantForeignKey, $tenantKey)
            ->where('is_active', true)
            ->first();

        if (! $widget instanceof ChatWidget) {
            throw new NotFoundHttpException();
        }

        return $widget;
    }

    /**
     * Find a chat conversation by its public UUID.
     */
    private function findConversationByUuid(string $uuid): ChatConversation
    {
        $conversation = ChatConversation::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->first();

        if (! $conversation instanceof ChatConversation) {
            throw new NotFoundHttpException();
        }

        return $conversation;
    }

    /**
     * Build the payload of messages for a conversation.
     *
     * @return array<int, array{id: int, sender_type: string, message: string, created_at: string}>
     */
    private function messagesPayload(ChatConversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $message): array => $this->messageToArray($message))
            ->all();
    }

    /**
     * Convert a single message to its public array payload.
     *
     * @return array{id: int, sender_type: string, message: string, created_at: string}
     */
    private function messageToArray(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type instanceof ChatSenderType
                ? $message->sender_type->value
                : (string) $message->sender_type,
            'message' => $message->message,
            'created_at' => $message->created_at?->toIso8601String() ?? '',
        ];
    }
}
