<?php
namespace App\Class;


use \Mailjet\Resources;
use Mailjet\Client;

class Mailjet {
    
    private $api_key = 'ecff314e2825bfbe59c8f55243d13d2a';
    private $api_key_secret = '098e6773e47a57152f3e2e3e5fc5c354';

    

    public function send($to_email, $to_name, $subject, $content){
        
        $mj = new Client($this->api_key, $this->api_key_secret, true, ['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "nab.prado@outlook.fr",
                        'Name' => "Mandy"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'TemplateID' => 4112495,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'Variables' => [
                        'content' => $content
                    ]
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
    }

    public function sendReceipt($to_email, $to_name, $subject, $firstname, $total_price, $order_date, $order_id){

        $mj = new Client($this->api_key, $this->api_key_secret, true, ['version' => 'v3.1']);

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "nab.prado@outlook.fr",
                        'Name' => "Mandy"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'TemplateID' => 4135870,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'Variables' => [
                        'firstname' => $firstname,
                        'total_price' => $total_price,
                        'order_date' => $order_date,
                        'order_id' => $order_id
                    ]
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
        
    }
}