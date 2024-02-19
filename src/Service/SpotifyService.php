<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyService
{
    const ENDPOINT = "https://api.spotify.com/v1/";
    const USERS = "users";

    const GET_TOKEN = "https://accounts.spotify.com/api/token";
    const PLAYLISTS = "playlists";
    const TRACKS = "tracks";
    const SPOTIFY_TOKEN = 'spotify_token';

    public function __construct(private EntityManagerInterface $em, private HttpClientInterface $client)
    {
        $this->em = $em;
        $this->client = $client;
        $cache = new FilesystemAdapter();
        $cacheToken = $cache->getItem(self::SPOTIFY_TOKEN);
        if ($cacheToken->isHit()) {
            $this->token = $cacheToken->get();
        } else {
            $authOptions = [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => ['client_id' => $_ENV['SPOTIFY_ID'], 'client_secret' => $_ENV['SPOTIFY_SECRET'], 'grant_type' => 'client_credentials']
            ];
            $response = $this->client->request('POST', self::GET_TOKEN, $authOptions);
            $tokenReturn = json_decode($response->getContent());
            $cacheItem = $cache->getItem(self::SPOTIFY_TOKEN);
            $this->token = $tokenReturn->access_token;
            $cacheItem->set($this->token);
            $cacheItem->expiresAfter(3600);
            $cache->save($cacheItem);
        }

        $this->options = ['headers' => ['Authorization' => 'Bearer ' . $this->token]];
    }

    public function getPlaylist($playlistId)
    {
        $response = $this->client->request('GET', self::ENDPOINT . self::PLAYLISTS .'/'. $playlistId, $this->options);
        return $response->toArray();

    }

    public function getUser($userCode)
    {
        $body = http_build_query([
            'code' => $userCode,
            'redirect_uri' => "https://localhost:8000/authorize/spotify",
            'grant_type' => 'authorization_code'
        ]);
        $options = ['headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Basic ' . base64_encode($_ENV['SPOTIFY_ID'] . ":" . $_ENV['SPOTIFY_SECRET'])],
            'body' => $body];
        $response = $this->client->request('POST', self::GET_TOKEN, $options);
        $tokenReturn = json_decode($response->getContent());
        return $response->toArray();
    }

    public function createPlaylist($accountToken, $playlistId)
    {
        $accountDatas = $this->getAccount($accountToken);
        $playlist = $this->getPlaylist($playlistId);
        $body = json_encode(['name' => $playlist['name'].' - PLAYLIST CONVERTER', 'description' => $playlist['description'].' - Made by PLAYLIST CONVERTER', 'public' => true]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $accountToken['access_token'], 'Content-Type' => 'application/json'],
            'body' => $body];

        $rawNewPlaylist = $this->client->request('POST', self::ENDPOINT . self::USERS .'/'. $accountDatas['id'] .'/'. self::PLAYLISTS, $options);
        $newPlaylist = $rawNewPlaylist->toArray();
        $uris = [];
        $i=0;
        $c=0;
        $totalTracks = count($playlist['tracks']['items']);
        //ONLY 100 TRACKS - IL FAUDRA SUIVRE LE SYSTEME DE PAGINATION POUR FAIRE LES TRACKS SUIVANTS
        foreach ($playlist['tracks']['items'] as $track){
            $uris[] .= $track['track']['uri'];
            $i++;
            if($i == 100 || $i+($c*100) == $totalTracks){
                $c++;
                $i=0;
                $body = json_encode($uris);
                $trackOptions = ['headers' => ['Authorization' => 'Bearer ' . $accountToken['access_token'], 'Content-Type' => 'application/json'],
                    'body' => $body];
                $this->client->request('POST', self::ENDPOINT.self::PLAYLISTS.'/'.$newPlaylist['id'].'/'.self::TRACKS, $trackOptions);
                $uris = [];
            }
        }
    }


    private function getAccount($accountToken)
    {
        $options = ['headers' => ['Authorization' => 'Bearer ' . $accountToken['access_token']]];
        $response = $this->client->request('GET', self::ENDPOINT . 'me', $options);
        return $response->toArray();

    }


}