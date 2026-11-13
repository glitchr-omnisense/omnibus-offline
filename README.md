# omnibus/offline

No carrier, for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus):
the shop hands parcels over itself (its own courier, a post office counter) or keeps them for collection.

## Install and a first call

```sh
composer require omnibus/offline
```

No account and no network: this package calls nothing.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnibus\Model\{Address, Parcel, Shipment};
use Omnibus\Offline\OfflineGatewayFactory;

// No carrier and no network: the shop's own rates (grams => cents) and a tracking number typed by hand.
$gateway = (new OfflineGatewayFactory())->create([
    'rates' => [['service' => 'courier', 'label' => 'Courier', 'bands' => [1000 => 690, 30000 => 1290]]],
    'tracking_url' => 'https://www.laposte.fr/outils/suivre-vos-envois?code={number}',
]);
$shipment = new Shipment(
    new Address('The shop', ['1 rue de Rivoli'], '75001', 'Paris', 'FR'),
    new Address('Camille Durand', ['12 rue des Juifs'], '67000', 'Strasbourg', 'FR'),
    [new Parcel(500)],
    options: ['tracking_number' => '6A12345678901'],
);
echo $gateway->rate($shipment)[0]->amount, "\n";        // 690
echo $gateway->ship($shipment)->trackingNumber, "\n";   // 6A12345678901
```

## Configuration

```php
$gateway = (new OfflineGatewayFactory())->create($options);   // the options below
```

No framework needed, and no HTTP client: the package requires `omnisense/omnibus` alone and calls
nothing. In a Symfony application, the same through the bundle's configuration:

```yaml
omnibus:
    gateways:
        retrait:
            factory: offline
            options:
                rates: [...]   # Omnibus\Action\ConfiguredRatingAction
                tracking_url: 'https://www.laposte.fr/outils/suivre-vos-envois?code={number}'
                pickup_points: # click & collect
                    - { id: shop, name: 'La boutique', street: ['1 rue ...'], postcode: '75001', city: Paris, country: FR }
```

License: MIT since 2026-10-10; earlier versions remain published under LGPL-3.0-or-later.
