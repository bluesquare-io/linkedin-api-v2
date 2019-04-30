<?php

require __DIR__.'/../vendor/autoload.php';

$client_id = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$redirect_uri = 'YOUR_REDIRECT_URI';
$scopes = ['r_liteprofile', 'r_emailaddress', 'w_member_social']; // optional

$client = new \Bluesquare\LinkedInAPIv2\LinkedInClient($client_id, $client_secret, $redirect_uri, $scopes);

if (!$client->checkAuthorizationCallback())
{
    if ($client->authorizationFailed())
    {
        echo "You cancelled LinkedIn auth.";
    }
    else {
        $url = $client->getAuthorizationURL();
        echo <<<HTML
<a href="$url">Connect with LinkedIn</a>
HTML;

        // or you can redirect directly:
        // $client->authorize();
        die;
    }
}

$url = $client->getAuthorizationURL();
echo <<<HTML
<a href="$url">Try again</a>
HTML;

$user = $client->getUserClient();

var_dump($user->getLiteProfile());
var_dump($user->getEmailAddress());