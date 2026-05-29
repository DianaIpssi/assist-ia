<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AddressApiService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Fonction qui interroge l'API adresse du gouvernement.
     *
     * @return array<int, array{label: string, city: string, postcode: string, lat: float, lon: float}>
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => [
                    'q' => $query,
                    'limit' => 5,
                ],
            ]);
        } catch (TransportExceptionInterface) {
            return [];
        }

        $payload = $response->toArray(false);
        $features = $payload['features'] ?? [];
        if (!\is_array($features)) {
            return [];
        }

        $results = [];
        foreach ($features as $feature) {
            if (!\is_array($feature)) {
                continue;
            }

            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? [];
            $coordinates = $geometry['coordinates'] ?? [];

            if (!\is_array($properties) || !\is_array($coordinates) || \count($coordinates) < 2) {
                continue;
            }

            $results[] = [
                'label' => (string) ($properties['label'] ?? ''),
                'city' => (string) ($properties['city'] ?? ''),
                'postcode' => (string) ($properties['postcode'] ?? ''),
                'lat' => (float) $coordinates[1],
                'lon' => (float) $coordinates[0],
            ];
        }

        return $results;
    }
}
