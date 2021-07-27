<?php

// This class was heavily inspired by the corresponding class from the
// feeds_http_auth_fetcher Drupal module by Michael Favia and is used
// under the provisions of the GNU General Public License version 2.0.
// See https://www.drupal.org/project/feeds_http_auth_fetcher for more
// details.

namespace Drupal\os2forms_forloeb\Feeds\Fetcher;


use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Response;
use Drupal\feeds\FeedInterface;

function get_openid_auth_token() {
    // TODO: This is a utility token to get an OpenID Connect bearer token from Keycloak.
    // At some point, this should be moved to a "utilities" file.

    $keycloak_configuration = \Drupal::config('openid_connect.settings.keycloak');

    $keycloak_settings = $keycloak_configuration->get('settings');
    $keycloak_base = $keycloak_settings['keycloak_base'];
    $keycloak_realm = $keycloak_settings['keycloak_realm'];
    $client_id = $keycloak_settings['client_id'];
    $client_secret = $keycloak_settings['client_secret'];


    $token_url = $keycloak_base . '/realms/' . $keycloak_realm . '/protocol/openid-connect/token';

    $payload['grant_type'] = 'client_credentials';
    $payload['client_id'] = $client_id;
    $payload['client_secret'] = $client_secret;

    $json = json_encode($payload);
    $response = \Drupal::httpClient()->request(
        'POST', $token_url, [ 'form_params' => $payload ]
    );
    $status_code = $response->getStatusCode();

    if ($status_code == 200) {
        $body = json_decode($response->getBody(), true);
        $access_token = $body['access_token'];

        return $access_token;
    } else {
        return '';
    }
}


/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "keycloakauth",
 *   title = @Translation("Download from URL with Keycloak OpenID Authorization"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler with OpenID Authorization header."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm",
 *     "feed" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherFeedForm",
 *   }
 * )
 */
class KeycloakAuthFetcher extends HTTPFetcher {

    public function defaultFeedConfiguration() {
        $default_configuration = parent::defaultConfiguration();
        $default_configuration['token'] = '';
        return $default_configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(FeedInterface $feed, StateInterface $state) {
        $sink = $this->fileSystem->tempnam('temporary://', 'feeds_http_fetcher');
        $sink = $this->fileSystem->realpath($sink);

        $response = $this->get($feed->getSource(), $sink, $this->getCacheKey($feed), get_openid_auth_token());
        // @todo Handle redirects.
        // @codingStandardsIgnoreStart
        // $feed->setSource($response->getEffectiveUrl());
        // @codingStandardsIgnoreEnd

        // 304, nothing to see here.
        if ($response->getStatusCode() == Response::HTTP_NOT_MODIFIED) {
            $state->setMessage($this->t('The feed has not been updated.'));
            throw new EmptyFeedException();
        }

        return new HttpFetcherResult($sink, $response->getHeaders());
    }
  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   * @param string $sink
   *   The location where the downloaded content will be saved. This can be a
   *   resource, path or a StreamInterface object.
   * @param string $cache_key
   *   (optional) The cache key to find cached headers. Defaults to false.
   * @param string $token
   *   (optional) The AUthorization bearer token. Defaults to null.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   *
   * @see \GuzzleHttp\RequestOptions
   */
  protected function get($url, $sink, $cache_key = FALSE, $token = null) {
    $url = Feed::translateSchemes($url);

    $options = [RequestOptions::SINK => $sink];

    // This is the magic add the headers here so allows the request
    if($token !== null) {
      $options[RequestOptions::HEADERS]['Authorization'] = 'Bearer ' . $token;
    }
      // Add cached headers if requested.
    if ($cache_key && ($cache = $this->cache->get($cache_key))) {
      if (isset($cache->data['etag'])) {
        $options[RequestOptions::HEADERS]['If-None-Match'] = $cache->data['etag'];
      }
      if (isset($cache->data['last-modified'])) {
        $options[RequestOptions::HEADERS]['If-Modified-Since'] = $cache->data['last-modified'];
      }
    }

    try {
      $response = $this->client->get($url, $options);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    if ($cache_key) {
      $this->cache->set($cache_key, array_change_key_case($response->getHeaders()));
    }

    return $response;
  }
}
