<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable=[
        'sender_id',
        'receiver_id',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getReceiver(){
        if($this->sender_id === auth()->id()){
            return User::firstWhere('id', $this->receiver_id);
        }
        else{
            return User::firstWhere('id', $this->sender_id);
        }
    }

    public function isLastMessageReadByUser(): bool{
        $user  = auth()->user();
        $lastMessage = $this->messages()->latest()->first();

        if($lastMessage){
            return $lastMessage->read_at !== null && $lastMessage->sender_id == $user->id;
        }

        return false;
    }

    public function unReadMessagesCount(): int
    {
        return $unreadMessages = Message::where('conversation_id', $this->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->count();

    }
}
