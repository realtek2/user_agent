<?php

interface MessageInterface{

    public function getBody();
}

class Message implements MessageInterface
{

    public function getBody()
    {
        // TODO: Implement getBody() method.
    }
}

trait SendMessageTrait{
    public function sendMessage(MessageInterface $message){

    }
}

class User{

    use SendMessageTrait;
    /**
     * @var Chat
     */
    public $chat;
}


class Chat{

    use SendMessageTrait;
}



$user = new User();


$user->sendMessage(
    new Message()
);

$user->chat->sendMessage(
    new Message()
);