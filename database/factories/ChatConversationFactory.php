<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Database\Factories;

use Cegem360\FilamentChatWidget\Enums\ChatConversationStatus;
use Cegem360\FilamentChatWidget\Models\ChatConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatConversation>
 */
final class ChatConversationFactory extends Factory
{
    protected $model = ChatConversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-1 month', 'now');
        $tenantForeignKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        return [
            $tenantForeignKey => null,
            'visitor_name' => fake()->name(),
            'visitor_email' => fake()->safeEmail(),
            'visitor_ip' => fake()->ipv4(),
            'status' => ChatConversationStatus::Open,
            'assigned_to' => null,
            'unread_count' => fake()->numberBetween(0, 10),
            'last_message_at' => fake()->dateTimeBetween($startedAt, 'now'),
            'started_at' => $startedAt,
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ChatConversationStatus::Closed,
            'closed_at' => now(),
            'unread_count' => 0,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ChatConversationStatus::Pending,
        ]);
    }
}
