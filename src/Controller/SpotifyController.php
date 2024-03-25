<?php

    namespace App\Controller;

    use App\Service\SpotifyService;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpClient\Exception\ClientException;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\UX\Turbo\TurboBundle;

    class SpotifyController extends AbstractController
    {

        public function __construct(private SpotifyService $spotifyService)
        {
            $this->spotifyService = $spotifyService;
        }

        #[Route('/spotify/link', name: 'app_spotify_get_link')]
        public function spotifyGetLink(Request $request)
        {
            $playlistRawLink = $request->query->get('playlistLink');
            $startPos = strpos($playlistRawLink, "playlist/") + strlen("playlist/");
            $endPos = strpos($playlistRawLink, "?");
            $playlistId = substr($playlistRawLink, $startPos, $endPos - $startPos);
            try {
                $playlist = $this->spotifyService->getPlaylist($playlistId);
            } catch (ClientException $exception) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('platform/spotify.html.twig', ['error' => $exception, 'content' => $playlistRawLink]);
            }
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('spotify/playlist_found.html.twig', ['playlist' => $playlist]);
        }

//        CETTE ROUTE EST ACCESSIBLE PAR LE CALLBACK SPOTIFY
        #[Route('/authorize/spotify', name: 'app_auth_spotify')]
        public function authorizeSpotify(Request $request)
        {
            if ($request->query->get('code')) {
                $accountToken = $this->spotifyService->getUser($request->query->get('code'));
                $playlistId = $request->cookies->get('playlistId');
                $playlistPlatform = $request->cookies->get('playlistPlatform');
                try {
                    switch ($playlistPlatform) {
                        case 'spotify':
                           $this->spotifyService->createPlaylist($accountToken, $playlistId);
                            break;
                        case 'deezer':
                            break;
                        case 'applemusic':
                            break;
                    }
                } catch (\Exception $e) {
                    return $this->redirectToRoute('app_main', ['createdPlaylist'=>'error']);

                }
            }
            return $this->redirectToRoute('app_main', ['createdPlaylist'=>'success']);
        }

    }