<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'sender_id',
        'type', // 'text', 'image', 'system'
        'content',
        'is_read',
    ];

    protected $appends = ['formatted_time'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * Waktu sudah diformat di server agar tidak tergantung timezone JS.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i');
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
