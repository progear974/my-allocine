<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\User;
use App\Form\FilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Repository\UserRepository;
use App\Service\APITMDB;
use Doctrine\DBAL\Types\StringType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;





class HomeController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(APITMDB $api): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }
        $genresArr = $api->getGenres();
        $id = 53;
        $obj = current(array_filter($genresArr, function ($element) use ($id) {
            return $element['id'] == $id;
        }));
        $listFilm = $api->getListFilmOfGenre($obj['id'])["results"];
        return $this->render('home/home.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @Route("/dashboard", name="dashboard")
     * @param APITMDB $api
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function dashboard(APITMDB $api, Request $request, ManagerRegistry $doctrine): Response
    {
        $genresArr = $api->getGenres();
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);
        $id = $user->getFavGenre();

        if ($id == null)
            return $this->redirectToRoute("ask-genre");
        $obj = current(array_filter($genresArr, function ($element) use ($id) {
            return $element['id'] == $id;
        }));
        $listFilm = $api->getListFilmOfGenre($obj['id'])["results"];
        $listLanguages = $api->getLanguages();
        $filter = new FilterType();
        $listLanguages = array_filter($listLanguages, function ($e) {
            return $e["english_name"] != " ";
        });
        $listLanguagesKey = array_map(function ($e) {
            return $e["english_name"];
        }, $listLanguages);
        $listLanguagesIso = array_map(function ($e) {
            return $e["iso_639_1"];
        }, $listLanguages);
        $languages = array_combine($listLanguagesKey, $listLanguagesIso);
        ksort($languages);

        $form = $this->createFormBuilder($filter)
            ->add('genres', ChoiceType::class, [
                'mapped' => false,
                'choices' => array_combine(array_map(function ($e) {
                    return $e["name"];
                }, $genresArr), array_map(function ($e) {
                    return $e["id"];
                }, $genresArr)),
                'required' => false
            ])
            ->add('years', IntegerType::class, [
                'mapped' => false,
                'required' => false

            ])
            ->add('languages', ChoiceType::class, [
                'mapped' => false,
                'choices' => $languages,
                'required' => false

            ])
            ->add('save', SubmitType::class, ['label' => 'Filter'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            if ($form->get("genres")->getData() != null or $form->get("languages")->getData() != null or $form->get("years")->getData() != null)
                $listFilm = $api->getListFilmOfGenre($form->get("genres")->getData(), $form->get("languages")->getData(), $form->get("years")->getData())["results"];
            else
                $listFilm = $api->getListFilmOfGenre($obj['id'])["results"];
            // ... perform some action, such as saving the task to the database

        }

        return $this->render('home/dashboard_base.html.twig', [
            'title_page' => "Les films de votre genre préféré",
            'genresList' => $genresArr,
            'movieList' => $listFilm,
            'languageList' => $listLanguages,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/ask-genre", name="ask-genre")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function ask_genre(APITMDB $api, ManagerRegistry $doctrine, Request $request) : Response
    {
        $entityManager = $doctrine->getManager();
        $genresArr = $api->getGenres();
        $form = $this->createFormBuilder()
            ->add('genres', ChoiceType::class, [
                'choices' => array_combine(array_map(function ($e) {
                    return $e["name"];
                }, $genresArr), array_map(function ($e) {
                    return $e["id"];
                }, $genresArr)),
                "expanded" => true,
                "multiple" => false
            ])
            ->add('submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);
            $user->setFavGenre($data["genres"]);
            $entityManager->flush();
            return $this->redirectToRoute("dashboard");
        }

        return $this->render('home/ask-genre.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/new_like/{id_film}", name="new_like")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function new_like(ManagerRegistry $doctrine, Request $request, int $id_film, LoggerInterface $logger) : Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $likes = $user->getLikes();
        if ($likes == null)
            $likes = [];
        if (in_array($id_film, $likes) == false)
            $likes[] = $id_film;
        $user->setLikes($likes);
        $entityManager->flush();
        return new Response();
    }

    /**
     * @Route("/unlike/{id_film}", name="unlike")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function unlike(ManagerRegistry $doctrine, Request $request, int $id_film, LoggerInterface $logger) : Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $likes = $user->getLikes();
        if ($likes == null)
            $likes = [];
        if (($key = array_search($id_film, $likes)) !== false)
            unset($likes[$key]);
        $user->setLikes($likes);
        $entityManager->flush();
        return new Response();
    }

    /**
     * @Route("/is_liked/{id_film}", name="is_liked")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function is_liked(ManagerRegistry $doctrine, Request $request, int $id_film, LoggerInterface $logger) : Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $likes = $user->getLikes();
        if ($likes == null)
            $likes = [];
        if (($key = array_search($id_film, $likes)) !== false)
            return new JsonResponse(['is_liked' => true]);
        return new JsonResponse(['is_liked' => false]);
    }

    /**
     * @Route("/new_view/{id_film}", name="new_view")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function new_view(ManagerRegistry $doctrine, Request $request, int $id_film, LoggerInterface $logger) : Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $views = $user->getViewed();
        if ($views == null)
            $views = [];
        if (in_array($id_film, $views) == false)
            $views[] = $id_film;
        $user->setViewed($views);
        $entityManager->flush();
        return new Response();
    }

    /**
     * @Route("/unview/{id_film}", name="unview")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function unview(ManagerRegistry $doctrine, Request $request, int $id_film, LoggerInterface $logger) : Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $views = $user->getViewed();
        if ($views == null)
            $views = [];
        if (($key = array_search($id_film, $views)) !== false)
            unset($views[$key]);
        $user->setViewed($views);
        $entityManager->flush();
        return new Response();
    }

    /**
     * @Route("/is_viewed/{id_film}", name="is_viewed")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function is_viewed(ManagerRegistry $doctrine, Request $request, int $id_film, LoggerInterface $logger) : Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $views = $user->getViewed();
        if ($views == null)
            $views = [];
        if (($key = array_search($id_film, $views)) !== false)
            return new JsonResponse(['is_viewed' => true]);
        return new JsonResponse(['is_viewed' => false]);
    }


    /**
     * @Route("/my-fav", name="my-fav")
     * @param APITMDB $api
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function my_fav(APITMDB $api, Request $request, ManagerRegistry $doctrine): Response
    {
        $genresArr = $api->getGenres();
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $id = $user->getFavGenre();

        if ($id == null)
            return $this->redirectToRoute("ask-genre");

        $listFilmId = $user->getLikes();
        $listFilm = [];
        foreach ($listFilmId as $id) {
            $listFilm[] = $api->getMovieDetail($id);
        }

        $listLanguages = $api->getLanguages();
        $filter = new FilterType();
        $listLanguages = array_filter($listLanguages, function ($e) {
            return $e["english_name"] != " ";
        });
        $listLanguagesKey = array_map(function ($e) {
            return $e["english_name"];
        }, $listLanguages);
        $listLanguagesIso = array_map(function ($e) {
            return $e["iso_639_1"];
        }, $listLanguages);
        $languages = array_combine($listLanguagesKey, $listLanguagesIso);
        ksort($languages);

        $form = $this->createFormBuilder($filter)
            ->add('genres', ChoiceType::class, [
                'mapped' => false,
                'choices' => array_combine(array_map(function ($e) {
                    return $e["name"];
                }, $genresArr), array_map(function ($e) {
                    return $e["id"];
                }, $genresArr)),
                'required' => false
            ])
            ->add('years', IntegerType::class, [
                'mapped' => false,
                'required' => false

            ])
            ->add('languages', ChoiceType::class, [
                'mapped' => false,
                'choices' => $languages,
                'required' => false

            ])
            ->add('save', SubmitType::class, ['label' => 'Filter'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            if ($form->get("genres")->getData() != null or $form->get("languages")->getData() != null or $form->get("years")->getData() != null)
                $listFilm = $api->getListFilmOfGenre($form->get("genres")->getData(), $form->get("languages")->getData(), $form->get("years")->getData())["results"];
            else
                $listFilm = $api->getListFilmOfGenre($obj['id'])["results"];
            // ... perform some action, such as saving the task to the database

        }

        return $this->render('home/dashboard_personal.html.twig', [
            'title_page' => "Les films que j'ai liké",
            'genresList' => $genresArr,
            'movieList' => $listFilm,
            'languageList' => $listLanguages,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/my-view", name="my-view")
     * @param APITMDB $api
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function my_view(APITMDB $api, Request $request, ManagerRegistry $doctrine): Response
    {
        $genresArr = $api->getGenres();
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $id = $user->getFavGenre();

        if ($id == null)
            return $this->redirectToRoute("ask-genre");
        $listFilmId = $user->getViewed();
        $listFilm = [];
        if ($listFilmId != null) {
            foreach ($listFilmId as $id) {
                $listFilm[] = $api->getMovieDetail($id);
            }
        }

        $listLanguages = $api->getLanguages();
        $filter = new FilterType();
        $listLanguages = array_filter($listLanguages, function ($e) {
            return $e["english_name"] != " ";
        });
        $listLanguagesKey = array_map(function ($e) {
            return $e["english_name"];
        }, $listLanguages);
        $listLanguagesIso = array_map(function ($e) {
            return $e["iso_639_1"];
        }, $listLanguages);
        $languages = array_combine($listLanguagesKey, $listLanguagesIso);
        ksort($languages);

        $form = $this->createFormBuilder($filter)
            ->add('genres', ChoiceType::class, [
                'mapped' => false,
                'choices' => array_combine(array_map(function ($e) {
                    return $e["name"];
                }, $genresArr), array_map(function ($e) {
                    return $e["id"];
                }, $genresArr)),
                'required' => false
            ])
            ->add('years', IntegerType::class, [
                'mapped' => false,
                'required' => false

            ])
            ->add('languages', ChoiceType::class, [
                'mapped' => false,
                'choices' => $languages,
                'required' => false

            ])
            ->add('save', SubmitType::class, ['label' => 'Filter'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            if ($form->get("genres")->getData() != null or $form->get("languages")->getData() != null or $form->get("years")->getData() != null)
                $listFilm = $api->getListFilmOfGenre($form->get("genres")->getData(), $form->get("languages")->getData(), $form->get("years")->getData())["results"];
            else
                $listFilm = $api->getListFilmOfGenre($obj['id'])["results"];
            // ... perform some action, such as saving the task to the database

        }

        return $this->render('home/dashboard_personal.html.twig', [
            'title_page' => "Les films que j'ai vu",
            'genresList' => $genresArr,
            'movieList' => $listFilm,
            'languageList' => $listLanguages,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/find_movie_from_title", name="find_movie")
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function find_movie_from_title(APITMDB $api, Request $request, LoggerInterface $logger) : Response
    {
        if ($request->get("title") == null)
            return new JsonResponse(['movies' => null]);
        return new JsonResponse(['movies' => $api->getMoviesFromTitle($request->get("title"))]);
    }

    /**
     * @Route("/my-playlists", name="my-playlists")
     * @param APITMDB $api
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function my_playlists(APITMDB $api, Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $id = $user->getFavGenre();

        if ($id == null)
            return $this->redirectToRoute("ask-genre");

        $playlists = $user->getPlaylists();
        $data = [];
        foreach ($playlists as $playlist) {
            $data[] = ["name"=> $playlist->getName(), "id" => $playlist->getId(),  "movies"=> $playlist->getMovies()];
        }
        return $this->render('home/dashboard_playlist.html.twig', [
            'title_page' => "Mes playlists",
            'data' => $data
        ]);
    }

    /**
     * @Route("/my-playlist/{id}", name="my-playlist")
     * @param APITMDB $api
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function my_playlist(APITMDB $api, Request $request, ManagerRegistry $doctrine, $id): Response
    {
        $genresArr = $api->getGenres();
        $entityManager = $doctrine->getManager();
        $playlist = $entityManager->getRepository(Playlist::class)->findOneBy(["id" => $id]);
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $id = $user->getFavGenre();

        if ($id == null)
            return $this->redirectToRoute("ask-genre");
        $listFilmId = $playlist->getMovies();
        $listFilm = [];
        if ($listFilmId != null) {
            foreach ($listFilmId as $id) {
                $listFilm[] = $api->getMovieDetail($id);
            }
        }

        $listLanguages = $api->getLanguages();
        $filter = new FilterType();
        $listLanguages = array_filter($listLanguages, function ($e) {
            return $e["english_name"] != " ";
        });
        $listLanguagesKey = array_map(function ($e) {
            return $e["english_name"];
        }, $listLanguages);
        $listLanguagesIso = array_map(function ($e) {
            return $e["iso_639_1"];
        }, $listLanguages);
        $languages = array_combine($listLanguagesKey, $listLanguagesIso);
        ksort($languages);

        $form = $this->createFormBuilder($filter)
            ->add('genres', ChoiceType::class, [
                'mapped' => false,
                'choices' => array_combine(array_map(function ($e) {
                    return $e["name"];
                }, $genresArr), array_map(function ($e) {
                    return $e["id"];
                }, $genresArr)),
                'required' => false
            ])
            ->add('years', IntegerType::class, [
                'mapped' => false,
                'required' => false

            ])
            ->add('languages', ChoiceType::class, [
                'mapped' => false,
                'choices' => $languages,
                'required' => false

            ])
            ->add('save', SubmitType::class, ['label' => 'Filter'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            if ($form->get("genres")->getData() != null or $form->get("languages")->getData() != null or $form->get("years")->getData() != null)
                $listFilm = $api->getListFilmOfGenre($form->get("genres")->getData(), $form->get("languages")->getData(), $form->get("years")->getData())["results"];
            else
                $listFilm = $api->getListFilmOfGenre($obj['id'])["results"];
            // ... perform some action, such as saving the task to the database

        }

        return $this->render('home/dashboard_personal.html.twig', [
            'title_page' => "Les films de la playlist ".$playlist->getName(),
            'genresList' => $genresArr,
            'movieList' => $listFilm,
            'languageList' => $listLanguages,
            'form' => $form->createView()
        ]);
    }



    /**
     * @Route("/create-playlist", name="create-playlist")
     * @param APITMDB $api
     * @return Response
     * @IsGranted("ROLE_USER")
     */
    public function create_playlist(APITMDB $api, Request $request, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(["email" => $this->getUser()->getUserIdentifier()]);

        $id = $user->getFavGenre();

        if ($id == null)
            return $this->redirectToRoute("ask-genre");

        $form = $this->createFormBuilder()
            ->add("name", TextType::class)
            ->add("submit", SubmitType::class)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($entityManager->getRepository(Playlist::class)->findOneBy(["name" => $data["name"]]))
                return $this->redirectToRoute("my-playlists");
            $playlist = new Playlist();
            $playlist->setName($data["name"]);
            $playlist->setUser($user);
            $entityManager->persist($playlist);
            $entityManager->flush();
            return $this->redirectToRoute("my-playlists");
        }

        return $this->render('home/dashboard_create_playlist.html.twig', [
            'title_page' => "Créer une playlist",
            'form' => $form->createView()
        ]);
    }
}