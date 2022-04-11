<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\User;
use App\Service\APITMDB;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DetailMovieController extends AbstractController
{
    /**
     * @Route("/movie/{id}", name="app_detail_movie")
     */
    public function index(APITMDB $api, $id, ManagerRegistry $doctrine,  Request $request): Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);
        $playlists = $user->getPlaylists()->toArray();
        $id_fav = $user->getFavGenre();

        if ($id_fav == null)
            return $this->redirectToRoute("ask-genre");


        $form = $this->createFormBuilder()
            ->add('playlist', ChoiceType::class, [
                'choices' => array_combine(array_map(function ($e) {
                    return $e->getName();
                }, $playlists), array_map(function ($e) {
                    return $e;
                }, $playlists))
            ])->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $playlist = $data["playlist"];
            if (!in_array($id, $playlist->getMovies())) {
                $movies = $playlist->getMovies();
                $movies[] = $id;
                $playlist->setMovies($movies);
            }
            $entityManager->persist($playlist);
            $entityManager->flush();
        }

        return $this->render('detail_movie/index.html.twig', [
            'controller_name' => 'DetailMovieController',
            'movie' => $api->getMovieDetail($id),
            'credit' => $api->getMovieCredits($id),
            'form' => $form->createView()
        ]);
    }
}
