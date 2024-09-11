<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\User;
use Livewire\Component;

class Users extends Component
{
    public function message($user_id){
        $authenticated_user_id = auth()->user()->id;

        $existing_conversation = Conversation::where(function ($query) use ($user_id, $authenticated_user_id) {
            $query->where('sender_id', $authenticated_user_id)
                ->where('receiver_id', $user_id);
        })->orWhere(function ($query) use ($user_id, $authenticated_user_id) {
            $query->where('sender_id', $user_id)
                ->where('receiver_id', $authenticated_user_id);
        })->first();

        if($existing_conversation){
            return redirect()->route('chat', ['query' => $existing_conversation->id]);
        }

       $created_conversation = Conversation::create([
            'sender_id' => $authenticated_user_id,
            'receiver_id' => $user_id
        ]);

        return redirect()->route('chat', ['query' => $created_conversation->id]);
    }
    public function render()
    {

        return view('livewire.users', [
            'users' => User::where('id', '!=', auth()->user()->id)->get()
        ])
        ->layout('layouts.app');
    }
}
