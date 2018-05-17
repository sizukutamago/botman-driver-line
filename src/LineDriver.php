<?php
/**
 * Created by PhpStorm.
 * User: sizukutamago
 * Date: 2018/05/17
 * Time: 15:56
 */

namespace Sizukutamago\Botman\Line;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Interfaces\WebAccess;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LineDriver extends HttpDriver
{
    const DRIVER_NAME = 'Line';

    const API_ENDPOINT = 'https://api.line.me/v2/bot/';

    /**
     * 検証用の署名です
     * @var string
     */
    protected $signature;

    protected $content;

    /** @var array */
    protected $messages = [];

    public function buildPayload(Request $request)
    {
        $this->config = Collection::make($this->config->get('line', []));
        $this->payload = new ParameterBag((array)json_decode($request->getContent(), true));
        $this->event = Collection::make((array)$this->payload->get('events')[0]);
        $this->content = $request->getContent();
        $this->signature = $request->headers->get('X-Line-Signature', '');
    }

    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        // TODO: Implement sendRequest() method.
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return $this->validateSignature() && $this->event->get('source')['type'] === 'user';
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $this->messages[] = new IncomingMessage(
                $this->event->get('message')['text'],
                $this->event->get('replyToken'),
                $this->event->get('source')['userId'],
                $this->payload
            );
        }

        return $this->messages;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->config->get('access_token'));
    }

    protected function validateSignature()
    {
        return hash_equals(
            base64_encode(hash_hmac(
                'sha256', $this->content, $this->config->get('channel_secret'), true
            )),
            $this->signature
        );
    }

    /**
     * Retrieve User information.
     * @param IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($this->event->get('source')['userId']);//todo get profile
    }

    /**
     * @param IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param string|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $payload = [
            'messages' => [],
            'replyToken' => $this->event->get('replyToken'),
        ];

        if ($message instanceof OutgoingMessage) {
            $payload['messages'][0]['text'] = $message->getText();
        } else {
            $payload['messages'][0]['text'] = $message;
        }

        $payload['messages'][0]['type'] = 'text';

        return $payload;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        $response = $this->http->post(self::API_ENDPOINT . 'message/reply', [], $payload, [
            "Authorization: Bearer {$this->config->get('access_token')}",
            'Content-Type: application/json',
        ], true);

        return $response;
    }
}
