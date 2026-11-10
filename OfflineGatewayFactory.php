<?php

namespace Omnibus\Offline;

use Omnibus\Config;
use Omnibus\GatewayFactory;
use Omnibus\Offline\Action\PickupAction;
use Omnibus\Offline\Action\ShippingAction;
use Omnibus\Offline\Action\TrackingAction;

/**
 * No carrier API: the shop hands the parcel over itself (its own courier, a
 * counter at the post office) or keeps it for collection.
 *
 *   options:
 *     rates: [...]                        # Omnibus\Action\ConfiguredRatingAction
 *     tracking_url: 'https://www.laposte.fr/outils/suivre-vos-envois?code={number}'
 *     pickup_points:                      # click & collect
 *       - { id: shop, name: 'La boutique', street: ['1 rue ...'], postcode: '75001', city: Paris, country: FR,
 *           opening_hours: { 1: [['10:00', '19:00']] } }
 */
final class OfflineGatewayFactory extends GatewayFactory
{
    protected function populateConfig(Config $config): void
    {
        $config->defaults([
            'omnibus.factory_name' => 'offline',
            'omnibus.factory_title' => 'Offline',
            'omnibus.policy' => __DIR__.'/policy.json',
            'tracking_url' => null,
            'pickup_points' => [],
            'omnibus.action.shipping' => static fn (Config $c) => new ShippingAction($c['tracking_url']),
            'omnibus.action.tracking' => new TrackingAction(),
            'omnibus.action.pickup' => static fn (Config $c) => new PickupAction($c['pickup_points']),
        ]);
    }
}
