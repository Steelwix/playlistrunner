<?php

    namespace App\Service;

    use AllowDynamicProperties;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\Cache\Adapter\FilesystemAdapter;
    use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    #[AllowDynamicProperties] class AppleService
    {
        const ENDPOINT = 'https://api.music.apple.com/v1/';
        const PLAYLISTS = 'me/library/playlists/';
        const GET_TOKEN = 'https://appleid.apple.com/auth/token';
        const APPLE_TOKEN = 'apple_token';
        public function __construct(private EntityManagerInterface $em, private HttpClientInterface $client)
        {
            $this->em = $em;
            $this->client = $client;
            $cache = new FilesystemAdapter();
            $cacheToken = $cache->getItem(self::APPLE_TOKEN);
            if ($cacheToken->isHit()) {
                $this->token = $cacheToken->get();
            } else {
                $authOptions = [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => ['client_id' => $_ENV['SPOTIFY_ID'], 'client_secret' => $_ENV['SPOTIFY_SECRET'], 'grant_type' => 'client_credentials']
                ];
                $response = $this->client->request('POST', self::GET_TOKEN, $authOptions);
                $tokenReturn = json_decode($response->getContent());
                $cacheItem = $cache->getItem(self::APPLE_TOKEN);
                $this->token = $tokenReturn->access_token;
                $cacheItem->set($this->token);
                $cacheItem->expiresAfter(3600);
                $cache->save($cacheItem);
            }

            $this->options = ['headers' => ['Authorization' => 'Bearer ' . $this->token]];
        }

        /**
         * @throws TransportExceptionInterface
         * @throws ServerExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws ClientExceptionInterface
         */
        public function getPlaylist($playlistId): array
        {
            $response = $this->client->request('GET', self::ENDPOINT . self::PLAYLISTS . '/' . $playlistId);
            dd($response);
            return $response->toArray();
        }
    }