<?php
/**
 * @author Maxime Renou <maxime@bluesquare.io>, Loann Meignant <loann@bluesquare.io>
 */

namespace Bluesquare\LinkedInAPIv2;

class LinkedInUserClient
{
    protected $access_token;

    /**
     * LinkedInUserClient constructor.
     * @param string $access_token
     */
    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * Get lite profile
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLiteProfile()
    {
        $client = new \GuzzleHttp\Client();

        $res = $client->request('GET', 'https://api.linkedin.com/v2/me', [
            'query' => [
                'projection' => '(id,localizedFirstName,localizedLastName,localizedHeadline,profilePicture(displayImage~:playableStreams))',
                'oauth2_access_token' => $this->access_token
            ]
        ]);

        $body = $res->getBody();

        if ($res->getStatusCode() !== 200)
            throw new \Exception("LinkedIn lite profile request failed: ".$body);

        if (($data = \json_decode($body, true)) === false)
            throw new \Exception("LinkedIn lite profile invalid JSON: ".$body);

        $profile = new \stdClass();

        if (isset($data['localizedFirstName']))
            $profile->firstName = $data['localizedFirstName'];

        if (isset($data['localizedLastName']))
            $profile->lastName = $data['localizedLastName'];

        if (isset($data['id']))
            $profile->id = $data['id'];

        if (isset($data['profilePicture'])) {
            try {
                $profile->profilePicture = $data['profilePicture']['displayImage~']['elements'][0]['identifiers'][0]['identifier'];
            }
            catch (\Throwable $e) {}
        }

        return $profile;
    }

    /**
     * Get email address
     * @returns null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getEmailAddress()
    {
        $client = new \GuzzleHttp\Client();

        $res = $client->request('GET', 'https://api.linkedin.com/v2/emailAddress', [
            'query' => [
                'q' => 'members',
                'projection' => '(elements*(handle~))',
                'oauth2_access_token' => $this->access_token
            ]
        ]);

        $body = $res->getBody();

        if ($res->getStatusCode() !== 200)
            throw new \Exception("LinkedIn email address request failed: ".$body);

        if (($data = \json_decode($body, true)) === false)
            throw new \Exception("LinkedIn email address invalid JSON: ".$body);

        $email = false;

        try {
            $email = $data['elements'][0]['handle~']['emailAddress'];
        }
        catch (\Throwable $e) {}

        return $email;
    }
}