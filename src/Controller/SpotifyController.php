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


        #[Route('/authorize/spotify', name: 'app_auth_spotify')]
        public function authorizeSpotify(Request $request)
        {

            if($request->query->get('code')){
                $accountToken = $this->spotifyService->getUser($request->query->get('code'));

                $playlistId = $request->cookies->get('playlistId');
                $this->spotifyService->createPlaylist($accountToken, $playlistId);
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                dd("PLAYLIST CREATED");
//        return $this->render('account/'.$accountPlatform.'.html.twig',['platform'=>$playlistPlatform, 'playlist'=>$playlistId]);
            }
        }
//        #[Route('/{playlistPlatform}/playlist/{playlistId}/{accountPlatform}/link', name: 'app_spotify_get_account')]
//        public function spotifyGetAccount(Request $request, $playlistPlatform, $playlistId, $accountPlatform){

//            $accountRawLink = $request->query->get('accountLink'); //https://open.spotify.com/user/11168150783?si=ffdc02e3b2ff4e54
//            $startPos = strpos($accountRawLink, "user/") + strlen("user/");
//            $endPos = strpos($accountRawLink, "?");
//            $accountId = substr($accountRawLink, $startPos, $endPos - $startPos);
//            $account = $this->spotifyService->createPlaylist($accountId);

//        }





    }