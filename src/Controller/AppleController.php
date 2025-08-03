<?php

    namespace App\Controller;

    use AllowDynamicProperties;
    use App\Service\AppleService;
    use JetBrains\PhpStorm\NoReturn;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpClient\Exception\ClientException;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\UX\Turbo\TurboBundle;

    #[AllowDynamicProperties] class AppleController extends AbstractController
    {
        public function __construct(AppleService $appleService)
        {
            $this->appleService = $appleService;
        }
        #[Route('/apple/link', name: 'app_apple_get_link')]
        public function appleGetLink(Request $request)
        {
            $playlistRawLink = $request->query->get('playlistLink');
            dd($playlistRawLink);
            $this->appleService->getPlaylist($playlistRawLink);
//            https://music.apple.com/fr/playlist/need-for-speed-unbound/pl.41748889072d481fbcdfe39dee3c4343

//            $startPos = strpos($playlistRawLink, "playlist/") + strlen("playlist/");
//            $endPos = strpos($playlistRawLink, "?");
//            $playlistId = substr($playlistRawLink, $startPos, $endPos - $startPos);
//            try {
//                $playlist = $this->spotifyService->getPlaylist($playlistId);
//            } catch (ClientException $exception) {
//                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
//                return $this->render('platform/spotify.html.twig', ['error' => $exception, 'content' => $playlistRawLink]);
//            }
//            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
//            return $this->render('spotify/playlist_found.html.twig', ['playlist' => $playlist]);
        }
    }