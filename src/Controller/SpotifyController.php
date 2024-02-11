<?php

    namespace App\Controller;

    use App\Service\SpotifyService;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\UX\Turbo\TurboBundle;

    class SpotifyController extends AbstractController
    {

        public function __construct(private EntityManagerInterface $em, private SpotifyService $spotifyService)
        {
            $this->em = $em;
            $this->spotifyService = $this->spotifyService;
        }

        #[Route('/spotify/link', name: 'app_spotify_get_link')]
        public function spotifyGetLink(Request $request)
        {
            $playlistRawLink = $request->query->get('playlistLink');
            $startPos = strpos($playlistRawLink, "playlist/") + strlen("playlist/");
            $endPos = strpos($playlistRawLink, "?");
            $playlistId = substr($playlistRawLink, $startPos, $endPos - $startPos);
            $playlist = $this->spotifyService->getPlaylist($playlistId);
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('spotify/playlist_found.html.twig', ['playlist' => $playlist]);
        }
    }