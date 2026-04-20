<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Database\Factories;

use Madbox99\FilamentChatWidget\Enums\ChatSenderType;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
final class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_conversation_id' => ChatConversation::factory(),
            'sender_type' => fake()->randomElement(ChatSenderType::cases()),
            'sender_id' => null,
            'message' => fake()->paragraph(),
            'read_at' => null,
        ];
    }

    public function fromVisitor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sender_type' => ChatSenderType::Visitor,
            'sender_id' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes): array => [
            'read_at' => now(),
        ]);
    }
}
