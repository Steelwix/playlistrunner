<?php

    namespace App\Service;

    use AllowDynamicProperties;
    use Doctrine\ORM\EntityManagerInterface;
    use Psr\Cache\InvalidArgumentException;
    use Symfony\Component\Cache\Adapter\FilesystemAdapter;
    use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
    use Symfony\Contracts\Cache\ItemInterface;
    use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
    use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    #[AllowDynamicProperties] class SpotifyService
    {
        const ENDPOINT = "https://api.spotify.com/v1/";
        const USERS    = "users";

        const GET_TOKEN     = "https://accounts.spotify.com/api/token";
        const PLAYLISTS     = "playlists";
        const TRACKS        = "tracks";
        const SPOTIFY_TOKEN = 'spotify_token';

        /**
         * @throws TransportExceptionInterface
         * @throws InvalidArgumentException
         * @throws ServerExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws ClientExceptionInterface
         */
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
            $response = $this->client->request('GET', self::ENDPOINT . self::PLAYLISTS . '/' . $playlistId, $this->options);
            return $response->toArray();
        }

        /**
         * @throws RedirectionExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws ClientExceptionInterface
         * @throws TransportExceptionInterface
         * @throws ServerExceptionInterface
         */
        public function getUser($userCode): array
        {
            $uri = 'https://playlistconverter.org/authorize/spotify';
            if($_ENV['APP_ENV']=='dev'){
                $uri = 'http://localhost:8000/authorize/spotify';
            }
            $body = http_build_query([
                                         'code'         => $userCode,
                                         'redirect_uri' => $uri,
                                         'grant_type'   => 'authorization_code'
                                     ]);
            $options = [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'Authorization' => 'Basic ' . base64_encode($_ENV['SPOTIFY_ID'] . ":" . $_ENV['SPOTIFY_SECRET'])],
                'body'    => $body
            ];
            $response = $this->client->request('POST', self::GET_TOKEN, $options);
            $tokenReturn = json_decode($response->getContent());
            return $response->toArray();
        }

        /**
         * @throws RedirectionExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws ClientExceptionInterface
         * @throws TransportExceptionInterface
         * @throws ServerExceptionInterface
         */
        public function createPlaylist($accountToken, $playlistId): void
        {
            $accountDatas = $this->getAccount($accountToken);
            $playlist = $this->getPlaylist($playlistId);
            $tracks = $this->getTracks($playlist['id']);
            $body = json_encode(['name' => $playlist['name'] . ' - PLAYLIST CONVERTER', 'description' => $playlist['description'] . ' - Made by PLAYLIST CONVERTER', 'public' => true]);
            $options = [
                'headers' => ['Authorization' => 'Bearer ' . $accountToken['access_token'], 'Content-Type' => 'application/json'],
                'body'    => $body
            ];

            $rawNewPlaylist = $this->client->request('POST', self::ENDPOINT . self::USERS . '/' . $accountDatas['id'] . '/' . self::PLAYLISTS, $options);
            $newPlaylist = $rawNewPlaylist->toArray();
            $uris = [];
            $i = 0;
            $processed = 0;
            $totalTracks = count($tracks);
            foreach ($tracks as $track) {
                $uris[] .= $track['track']['uri'];
                $i++;
                if ($i == 100 || $i + ($processed * 100) == $totalTracks) {
                    $processed++;
                    $i = 0;
                    $body = json_encode($uris);
                    $trackOptions = [
                        'headers' => ['Authorization' => 'Bearer ' . $accountToken['access_token'], 'Content-Type' => 'application/json'],
                        'body'    => $body
                    ];
                    $this->client->request('POST', self::ENDPOINT . self::PLAYLISTS . '/' . $newPlaylist['id'] . '/' . self::TRACKS, $trackOptions);
                    $uris = [];
                }
            }
        }

        /**
         * @throws TransportExceptionInterface
         * @throws ServerExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws ClientExceptionInterface
         */
        private function getTracks($playlistId): array
        {
            $tracks = [];
            $collected = 0;
            $limit = 50;
            $offset = 0;
            $total = 1;
            while ($total > $collected) {
                $params = ['limit' => $limit, 'offset' => $offset];
                $options = $this->options;
                $options['query'] = $params;
                $rawResponse = $this->client->request('GET', self::ENDPOINT . self::PLAYLISTS . '/' . $playlistId . '/' . self::TRACKS, $options);
                $response = $rawResponse->toArray();
                if (empty($response['items'])) {
                    break;
                }
                $total = $response['total'];
                foreach ($response['items'] as $item) {
                    $tracks[] = $item;
                    $collected++;
                    $offset++;
                }
            }
            return $tracks;
        }


        /**
         * @throws TransportExceptionInterface
         * @throws ServerExceptionInterface
         * @throws RedirectionExceptionInterface
         * @throws DecodingExceptionInterface
         * @throws ClientExceptionInterface
         */
        private function getAccount($accountToken): array
        {
            $options = ['headers' => ['Authorization' => 'Bearer ' . $accountToken['access_token']]];
            $response = $this->client->request('GET', self::ENDPOINT . 'me', $options);
            return $response->toArray();
        }
    }
