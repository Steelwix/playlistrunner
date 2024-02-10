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
        public function spotifyGetLink(Request $request){
            $playlistRawLink = $request->query->get('playlistLink');
            // https://open.spotify.com/playlist/3j5gXW0Lm8l3x6nlGat9JC?si=d44fd5eee20b4884
            $startPos = strpos($playlistRawLink, "playlist/") + strlen("playlist/");
            $endPos = strpos($playlistRawLink, "?");
            $playlistId = substr($playlistRawLink, $startPos, $endPos - $startPos);
            $this->spotifyService->getPlaylist($playlistId);
            dd($playlistId);
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('platform/.html.twig');
        }
    }