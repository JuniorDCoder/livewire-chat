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
    public function scopeWhereNotDeleted($query){
        $userId = auth()->id();

        return $query->where(function($query) use ($userId){
            $query->whereHas('messages', function($query) use ($userId){
                $query->where(function($query) use ($userId){
                    $query->where('sender_id', $userId)->whereNull('sender_deleted_at');
                })->orWhere(function($query) use ($userId){
                    $query->where('receiver_id', $userId)->whereNull('receiver_deleted_at');
                });
            });
        })->orWhereDoesntHave('messages');
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
