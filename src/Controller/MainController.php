<?php

    namespace App\Controller;

    use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
    use Symfony\Component\HttpFoundation\Cookie;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\RedirectResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\UX\Turbo\TurboBundle;

    class MainController extends AbstractController
    {

        const SPOTIFY = 'spotify';
        const DEEZER = 'deezer';
        const APPLE_MUSIC = 'applemusic';

        #[Route('/', name: 'app_main')]
        public function index(Request $request) : Response
        {

            if($request->query->get('createdPlaylist')){
                switch ($request->query->get('createdPlaylist')){
                    case 'success':
                        $this->addFlash('success', 'You own a new playlist');
                        break;
                    case 'error':
                        $this->addFlash('error', 'Something went off when creating the playlist');
                        break;
                }
            }
            return $this->render('main/index.html.twig');
        }

        #[Route('/{platform}', name: 'app_platform_pick')]
        public function platformPick(Request $request, $platform){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('platform/'.$platform.'.html.twig');
        }

        #[Route('/{platform}/playlist/{playlistId}', name: 'app_account_platform_pick')]
        public function accountPlatformPick(Request $request, $platform, $playlistId){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('account/pick.html.twig', ['platform' => $platform,'playlist' => $playlistId]);
        }



        #[Route('/{playlistPlatform}/playlist/{playlistId}/{accountPlatform}', name: 'app_account_pick')]
        public function accountPick(Request $request, $playlistPlatform, $playlistId, $accountPlatform){

            switch ($accountPlatform){
                case 'spotify':
                    $uri = 'https://playlistconverter.org/authorize/spotify';
                    if($_ENV['APP_ENV']=='dev'){
                        $uri = 'http://localhost:8000/authorize/spotify';
                    }
                    $clientId =  $_ENV['SPOTIFY_ID'];
                    $redirectUri = $uri;
                    $cookiePlaylistPlatform = new Cookie('playlistPlatform', $playlistPlatform);
                    $cookiePlaylistId = new Cookie('playlistId', $playlistId);
                    $authorizeUrl = 'https://accounts.spotify.com/authorize';
                    $authorizeUrl .= '?response_type=code';
                    $authorizeUrl .= '&client_id=' . urlencode($clientId);
                    $authorizeUrl .= '&scope=' . urlencode('user-read-private user-read-email playlist-modify-public playlist-modify-private ');
                    $authorizeUrl .= '&redirect_uri=' . $redirectUri;
                    $response = new RedirectResponse($authorizeUrl);
                    //EN DIRECTION DE LA CONNEXION A SPOTIFY
                    $response->headers->setCookie($cookiePlaylistId);
                    $response->headers->setCookie($cookiePlaylistPlatform);
                    return $response;
                    break;
                case 'deezer':
                    break;
                case 'applemusic':
                    break;
            }

        }


    }
