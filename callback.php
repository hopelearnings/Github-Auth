<?php

Use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

include('vendor/autoload.php');

function exchangeCode($data, $apiUrl){

    $client = new Client();

    try{
        $response = $client->post($apiUrl, [
            'form_params' => $data,
            'headers' => [
                'Accept' => 'application/json'
            ]
            ]);

            if($response->getStatusCode() == 200){
                  return json_decode($response->getBody()->getContents());
            }
            return false;

    }catch(RequestException $e){
       return false;
    }

}

if (isset($_GET['error']) || !isset($_GET['code'])){
    echo'Some Error Occured';
    exit();
}


$authCode = $_GET['code'];

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();



$data = [

    'client_id' => $_ENV['GITHUB_CLIENT_ID'],
    'client_secret' => $_ENV['GITHUB_CLIENT_SECRECT'],
    'code' => $authCode,
];


$apiUrl = "https://github.com/login/oauth/access_token";


$tokenData = exchangeCode($data, $apiUrl);


if($tokenData === false){
    exit('Error token');
}


if(!empty($tokenData->Error)){
    exit($tokenData->error);
}

if (!empty($tokenData->access_token)){
    setcookie('cr_github_access_token', $tokenData->access_token, time() + 2592000, "/"
    ,"", false, true);
    
// The last argument -true - sets it as an httponly cookie
    
header('Location: Protected.php');
exit();
}



?>