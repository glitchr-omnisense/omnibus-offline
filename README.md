# omnibus/offline

No carrier, for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus):
the shop hands parcels over itself (its own courier, a post office counter) or keeps them for collection.

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
