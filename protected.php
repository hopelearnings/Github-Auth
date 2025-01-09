<?php


Use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


include('vendor/autoload.php');


$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();



function getUserWithEmails() {
    if (empty($_COOKIE['cr_github_access_token'])) {
        return false;
    }

    $accessToken = $_COOKIE['cr_github_access_token'];
    $client = new GuzzleHttp\Client();

    try {
        // Prepare requests
        $requests = [
            'user' => $client->getAsync('https://api.github.com/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]),
            'emails' => $client->getAsync('https://api.github.com/user/emails', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]),
        ];

        // Send requests concurrently
        $responses = GuzzleHttp\Promise\Utils::settle($requests)->wait();

        $user = null;

        // Handle user response
        if (isset($responses['user']['state']) && $responses['user']['state'] === 'fulfilled') {
            $user = json_decode($responses['user']['value']->getBody()->getContents());
        }

        // Handle email response
        if (isset($responses['emails']['state']) && $responses['emails']['state'] === 'fulfilled' && $user) {
            $emails = json_decode($responses['emails']['value']->getBody()->getContents(), true);

            // Assign the primary email
            foreach ($emails as $email) {
                if ($email['primary'] && $email['verified']) {
                    $user->email = $email['email'];
                    break;
                }
            }
        }

        return $user;
    } catch (Exception $e) {
        return false;
    }
}



$user = false;

$user = getUserWithEmails();

var_dump($user);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protected Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
     <div class="d-flex flex-column align-items-center justify-content-center min-vh-100">
        <?php if (!empty($user)):?>
            <img src="<?= htmlspecialchars($user->avatar_url) ?>" alt="" class="rounded-circle">
             <h1 class="alert alert-success mt-4">Welcome,  <?=  htmlspecialchars($user->name); ?></h1>
             <h1 class="alert alert-warning mt-4"> <?=  htmlspecialchars($user->email); ?></h1>
            <?php else: ?>
            <div class="alert alert-danger">Authentication Required</div>
            <a href="index.php" class="btn btn-primary btn-lg">SignIn</a>
         <?php endif; ?>
     </div>
</body>
</html>