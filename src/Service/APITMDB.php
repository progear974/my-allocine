<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class APITMDB {

    private $client;
    private $url;
    private $url_image;

    public function __construct(HttpClientInterface $client, $api_key)
    {
        $this->url = "https://api.themoviedb.org";
        $this->url_image = "https://image.tmdb.org/t/p/w500";
        $this->client = $client->withOptions([
            'base_uri' => $this->url,
            'query' => ['api_key' => $api_key, "language" => "fr"]
        ]);
    }

    public function getGenres()
    {
        $response = $this->client->request('GET', "/3/genre/movie/list");
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->toArray();
        return $content["genres"];
    }

    public function getListFilmOfGenre($id, $original_language="en", $year="-1", $page="1")
    {
        $response = $this->client->request('GET', "/3/discover/movie", [
            "query" => ["with_genres" => $id, "page" => $page, "with_original_language" => $original_language, "year" => $year]
        ]);
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->toArray();
        return $content;
    }

    public function getLanguages()
    {
        $response = $this->client->request('GET', "3/configuration/languages");
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->toArray();
        return $content;
    }

    public function getMovieDetail($id)
    {
        $response = $this->client->request('GET', "3/movie/".$id, [
            "query" => ["append_to_response" => "videos"]
        ]);
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->toArray();
        return $content;
    }

    public function getMovieCredits($id) {
        $response = $this->client->request('GET', "3/movie/".$id."/credits");
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->toArray();
        return $content;
    }

    public function getMoviesFromTitle($title) {
        $response = $this->client->request('GET', "3/search/movie", [
            "query" => ["query" => $title]
        ]);
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->toArray();
        return $content;
    }
}