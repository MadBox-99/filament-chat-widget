<?php

declare(strict_types=1);

namespace Cegem360\FilamentChatWidget\Database\Factories;

use Cegem360\FilamentChatWidget\Models\ChatWidget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatWidget>
 */
final class ChatWidgetFactory extends Factory
{
    protected $model = ChatWidget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenantForeignKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        return [
            $tenantForeignKey => null,
            'title' => fake()->words(2, true),
            'welcome_message' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'position' => fake()->randomElement(['bottom-right', 'bottom-left']),
            'is_active' => true,
            'offline_message' => fake()->sentence(),
            'business_hours' => null,
            'auto_reply_message' => fake()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
