<?php

namespace App\Tests\Unit\Service;

use App\Service\AddressApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AddressApiServiceTest extends TestCase
{
    public function testSearchReturnsEmptyArrayForBlankQuery(): void
    {
        $service = new AddressApiService(new MockHttpClient());

        $this->assertSame([], $service->search('   '));
    }

    public function testSearchMapsApiResponse(): void
    {
        $payload = json_encode([
            'features' => [
                [
                    'properties' => [
                        'label' => '10 Rue de Rivoli 75001 Paris',
                        'city' => 'Paris',
                        'postcode' => '75001',
                    ],
                    'geometry' => [
                        'coordinates' => [2.3522, 48.8566],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $client = new MockHttpClient([
            new MockResponse($payload),
        ]);

        $service = new AddressApiService($client);
        $results = $service->search('paris');

        $this->assertCount(1, $results);
        $this->assertSame('Paris', $results[0]['city']);
        $this->assertSame('75001', $results[0]['postcode']);
        $this->assertSame(48.8566, $results[0]['lat']);
    }

    public function testSearchReturnsEmptyArrayOnTransportError(): void
    {
        $client = new MockHttpClient(static function (): never {
            throw new TransportException('network down');
        });

        $service = new AddressApiService($client);

        $this->assertSame([], $service->search('lyon'));
    }
}
