<?php

namespace Vanguard\Services\Messaging;

use Vanguard\Message;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class PrivateChat implements MessageComponentInterface
{
    /**
     * @var array
     */
    protected $clients = [];

    /**
     * Event that is called when a new user connects to the WebSocket component.
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $chatId = $conn->WebSocket->request->getCookie('chat_id');

        if (!isset($this->clients[$chatId])) {
            $this->clients[$chatId] =  new \SplObjectStorage;
        }
        $this->clients[$chatId]->attach($conn);
    }

    /**
     * Event that is called when a message was received from a connected user.
     *
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        if ($msg === 'ping') {
            return;
        }

        $chatId = $from->WebSocket->request->getCookie('chat_id');
        $userId = $from->WebSocket->request->getCookie('user_id');

        if (json_decode($msg)->status === 'sent') {
            $this->storeMessage($msg, $chatId, $userId);
        }

        foreach ($this->clients[$chatId] as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    /**
     * Event that is called when a user disconnects from the WebSocket component.
     *
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        $chatId = $conn->WebSocket->request->getCookie('chat_id');
        $this->clients[$chatId]->detach($conn);
    }

    /**
     * Event that is called when an error occurred at some time during communication.
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "-------------------------------------\n";
        echo "An error has occurred:\n";
        echo "Message: {$e->getMessage()}\n";
        echo "File: {$e->getFile()}\n";
        echo "Line: {$e->getLine()}\n";
        echo "Code: {$e->getCode()}\n";
        echo "Trace: {$e->getTraceAsString()}\n";

        $conn->close();
    }

    /**
     * Stores a message in database.
     *
     * @param $msg
     * @param $chatId
     * @param $userId
     */
    private function storeMessage($msg, $chatId, $userId)
    {
        Message::create([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'body' => json_decode($msg)->body
        ]);
    }
}

