<?php

namespace App\Livewire\Chat;

use App\Models\Message;
use Livewire\Component;
use Livewire\Attributes\Rule;
use App\Notifications\MessageRead;
use App\Notifications\MessageSent;

class ChatBox extends Component
{
    public $selectedConversation;

    #[Rule('required|string')]
    public $body;

    public $loadedMessages;

    public $paginate_variable = 10;

    protected $listeners = [
        'loadMore'
    ];

    public function getListeners(){
        $auth_id = auth()->user()->id;
        return [
            'loadMore',
            "echo-private:users.{$auth_id},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => 'broadcastedNotifications',
        ];
    }

    public function broadcastedNotifications($event){
        if ($event['type'] == MessageSent::class) {

            if ($event['conversation_id'] == $this->selectedConversation->id) {

                $this->dispatch('scroll-bottom');

                $newMessage = Message::find($event['message_id']);


                #push message
                $this->loadedMessages->push($newMessage);


                #mark as read
                $newMessage->read_at = now();
                $newMessage->save();

                #broadcast
                $this->selectedConversation->getReceiver()
                    ->notify(new MessageRead($this->selectedConversation->id));
            }
        }
    }
    public function loadMore(): void
    {
        # Increment the pagination variable
        $this->paginate_variable += 10;

        # Load more messages
        $this->loadMessages();

        # Dispatch an event to update the chat height if needed
        $this->dispatch('update-chat-height');
    }


    public function loadMessages(){
        # get count of messages
        $count = $this->loadedMessages = Message::where('conversation_id', $this->selectedConversation->id)->count();
        $this->loadedMessages = Message::where('conversation_id', $this->selectedConversation->id)
        ->skip($count - $this->paginate_variable)
        ->take($this->paginate_variable)
        ->get();

        return $this->loadedMessages;
    }

    public function sendMessage(){
        $this->validate();
        $createdMessage = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $this->selectedConversation->getReceiver()->id,
            'body' => $this->body
        ]);

        $this->reset('body');

        # scroll to bottom
        $this->dispatch('scroll-bottom');
        # push the message
        $this->loadedMessages->push($createdMessage);

        # update conversation model
        $this->selectedConversation->updated_at = now();
        $this->selectedConversation->save();

        # emit event to update the conversation list
        $this->dispatch('refresh', $this->selectedConversation->id);

        #broadcast the message
        $this->selectedConversation->getReceiver()
        ->notify(new MessageSent(auth()->user(), $createdMessage, $this->selectedConversation, $this->selectedConversation->getReceiver()->id));
    }

    public function mount(){
        $this->loadMessages();
    }
    public function render()
    {
        return view('livewire.chat.chat-box');
    }
}
