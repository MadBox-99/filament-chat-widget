<?php

declare(strict_types=1);

namespace Madbox99\FilamentChatWidget\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Madbox99\FilamentChatWidget\Models\ChatConversation;
use Madbox99\FilamentChatWidget\Models\ChatWidget;

class Team extends Model
{
    protected $table = 'teams';

    protected $guarded = [];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    /**
     * @return HasMany<ChatWidget, $this>
     */
    public function chatWidgets(): HasMany
    {
        return $this->hasMany(ChatWidget::class, 'team_id');
    }

    /**
     * @return HasMany<ChatConversation, $this>
     */
    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'team_id');
    }
}
