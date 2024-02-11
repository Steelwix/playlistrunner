<?php

    namespace App\Service;

    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\Cache\Adapter\FilesystemAdapter;
    use Symfony\Contracts\Cache\ItemInterface;
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    class SpotifyService
    {
        const GET_TOKEN = "https://accounts.spotify.com/api/token";
        const GET_PLAYLIST = "https://api.spotify.com/v1/playlists/";
        const SPOTIFY_TOKEN = 'spotify_token';
        public function __construct(private EntityManagerInterface $em, private HttpClientInterface $client)
        {
            $this->em = $em;
            $this->client = $client;
            $cache = new FilesystemAdapter();
            $cacheToken = $cache->getItem(self::SPOTIFY_TOKEN);
            if($cacheToken->isHit()){
                $this->token = $cacheToken->get();
            } else {
                $authOptions = [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => ['client_id' => $_ENV['SPOTIFY_ID'], 'client_secret' => $_ENV['SPOTIFY_SECRET'], 'grant_type' => 'client_credentials']
                ];
                $response = $this->client->request('POST', self::GET_TOKEN, $authOptions);
                $tokenReturn = json_decode($response->getContent());
                $cacheItem = $cache->getItem(self::SPOTIFY_TOKEN);
                $this->token = $tokenReturn->access_token;
                $cacheItem->set($this->token);
                $cacheItem->expiresAfter(3600);
                $cache->save($cacheItem);
            }

            $this->options = ['headers' => ['Authorization' => 'Bearer '.$this->token]];
        }

        public function getPlaylist($playlistId){
            $response = $this->client->request('GET', self::GET_PLAYLIST.$playlistId, $this->options);
            return $response->toArray();

        }
    }