<?php
namespace Lab123\AwsSns\Channels;

use Lab123\AwsSns\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use Aws\Sns\SnsClient;

class AwsSnsSmsChannel
{

    public function __construct(SnsClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable            
     * @param \Illuminate\Notifications\Notification $notification            
     *
     * @throws \Lab123\AwsSns\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toAwsSnsSms($notifiable);
        $message->phoneNumber = ($message->phoneNumber) ?: $notifiable->routeNotificationFor('AwsSnsSms');
        
        if (! $message->phoneNumber || ! $message->message) {
            return;
        }
        
        // setting default message type to transactional.
        $this->client->setSMSAttributes(array('attributes' => ['DefaultSMSType' => 'Transactional', 'DefaultSenderID' => 'MW']));
        
        $data = [
            'PhoneNumber' => $message->phoneNumber,
            'MessageStructure' => $message->messageStructure,
            'Message' => $message->message
        ];
        
        $response = $this->client->publish($data);
        
        $response = $response->toArray();
        
        if ($response["@metadata"]["statusCode"] != 200) {
            throw CouldNotSendNotification::serviceRespondedWithAnError();
        }
    }
}
