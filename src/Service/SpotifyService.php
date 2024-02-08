<?php

    namespace App\Service;

    use Doctrine\ORM\EntityManagerInterface;

    class SpotifyService
    {
        const GET_PLAYLIST = "https://api.spotify.com/v1/playlists/";
        public function __construct(private EntityManagerInterface $em)
        {
            $this->em = $em;
        }
    }