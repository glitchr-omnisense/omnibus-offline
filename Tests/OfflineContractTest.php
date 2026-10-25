<?php

namespace Omnibus\Offline\Tests;

use Omnibus\GatewayFactoryInterface;
use Omnibus\Offline\OfflineGatewayFactory;
use Omnibus\Testing\GatewayContractTests;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** The conformance suite: no carrier behind it, so nothing recorded and no failure of one to report. */
final class OfflineContractTest extends TestCase
{
    use GatewayContractTests;

    protected function gatewayFactory(HttpClientInterface $http): GatewayFactoryInterface
    {
        return new OfflineGatewayFactory();
    }

    protected function gatewayOptions(): array
    {
        return [
            'tracking_url' => 'https://www.laposte.fr/outils/suivre-vos-envois?code={number}',
            'pickup_points' => [
                ['id' => 'shop', 'name' => 'La boutique', 'street' => ['1 rue de Rivoli'], 'postcode' => '75001', 'city' => 'Paris', 'country' => 'FR', 'opening_hours' => [2 => [['10:00', '19:00']]]],
            ],
        ];
    }

    protected function recordedAnswers(): array
    {
        return [];
    }

    /** The shop prints its own slip: the label is a tracking number and its link. */
    protected function issuesDocuments(): bool
    {
        return false;
    }
}
