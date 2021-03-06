<?php

namespace Mpociot\BotMan\Drivers;

use Illuminate\Support\Collection;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NexmoDriver extends Driver
{
    /** @var Collection|ParameterBag */
    protected $payload;

    /** @var Collection */
    protected $event;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = $request->request->all();
        $this->event = Collection::make($this->payload);
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('msisdn'));
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getMessage());
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        return [new Message($this->event->get('text'), $this->event->get('msisdn'), $this->event->get('to'), $this->payload)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge([
            'api_key' => $this->config->get('nexmo_key'),
            'api_secret' => $this->config->get('nexmo_secret'),
            'to' => $matchingMessage->getUser(),
            'from' => $matchingMessage->getChannel(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
        } else {
            $parameters['text'] = $message;
        }

        return $this->http->post('https://rest.nexmo.com/sms/json?'.http_build_query($parameters));
    }
}
