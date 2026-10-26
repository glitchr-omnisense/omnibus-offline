# omnibus/offline

No carrier, for [omnisense/omnibus](https://github.com/glitchr-omnisense/omnibus): the shop hands its
parcels over itself (its own courier, a post office counter) or keeps them for collection. The
gateway calls nothing: prices come from the `rates` option, the tracking number is the one the shop
typed, the pickup points are the shop's own counters (click and collect).

## Installation

```sh
composer require omnibus/offline
```

Requires PHP 8.2 and `omnisense/omnibus` (2.x), nothing else: no HTTP client, no framework. In a
Symfony application with `Omnibus\Bridge\Symfony\OmnibusBundle`, the factory is registered as soon
as the package is installed. `OfflineGatewayFactory` is built without an HTTP client.

## Credentials

None: there is no service behind the gateway.

## Options

No option is required: `(new OfflineGatewayFactory())->create()` builds a gateway.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `tracking_url` | ?string | `null` | no | the public tracking page given with each label; `{number}` is replaced by the tracking number, URL-encoded (`rawurlencode`) |
| `pickup_points` | list | `[]` | no | the shop's counters, returned by every pickup point search (below) |
| `rates` | list | - | no | the only way this gateway rates ([the rates option](https://github.com/glitchr-omnisense/omnibus/blob/2.x/docs/configuration.md#the-rates-option)) |

Each entry of `pickup_points`:

| Key | Type | Default | |
|---|---|---|---|
| `id` | string | - | required; the id to put in `Shipment::$pickupPoint` |
| `name` | string | - | required; the point's name, also its address's name |
| `street` | list of strings | `[]` | |
| `postcode` | string | `''` | |
| `city` | string | `''` | |
| `country` | string | `FR` | ISO 3166-1 alpha-2 |
| `phone` | ?string | `null` | |
| `latitude`, `longitude` | float | `null` | |
| `opening_hours` | map | `[]` | ISO weekday (1 = Monday) => list of `[open, close]` slots, `"HH:MM"`, passed on as written |

Shipment options read when booking (`Shipment::$options`):

| Option | Default | |
|---|---|---|
| `tracking_number` | the shipment's `reference` | the number from the post office's receipt or the courier's slip |

## Capabilities

| Request | Supported |
|---|---|
| `Rating` | only with `rates` (configured prices) |
| `Shipping` | yes (nothing is booked) |
| `Tracking` | yes, the status is always `UNKNOWN` |
| `Pickup` | yes (the configured `pickup_points`) |
| `GetSlip` | no |
| `Cancel` | no |
| `Paperless` | no |

## Behaviour

**Rate.** Without `rates` the gateway does not support `Rating`; with it, the core's
`ConfiguredRatingAction` prices each parcel on its weight band.

**Ship.** Nothing is booked and no document is made: the label has no `content` and no `url` (its
`format` keeps the model's default, `application/pdf`). The tracking number is, in order, the
shipment option `tracking_number`, the shipment's `reference`, or 12 random uppercase hexadecimal
characters. `trackingUrl` is `tracking_url` with `{number}` replaced, or `null` when `tracking_url`
is not set.

**Track.** No request: the answer is a `Tracking` with the number asked for, the status
`UNKNOWN` and no event. The locale is ignored.

**Pickup points.** The configured `pickup_points`, in the configured order, cut to the request's
`limit`. The address searched and the parcel are ignored (the shop's counters are offered wherever
the customer is), and `distance` is always `null`. The opening hours are kept as configured, each
slot cast to two strings: nothing checks that the keys are ISO weekdays or the times `HH:MM`.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnibus\Model\Address;
use Omnibus\Model\Parcel;
use Omnibus\Model\Shipment;
use Omnibus\Offline\OfflineGatewayFactory;

$offline = (new OfflineGatewayFactory())->create([
    'tracking_url' => 'https://www.laposte.fr/outils/suivre-vos-envois?code={number}',
    'rates' => [
        ['service' => 'letter', 'label' => 'Lettre suivie', 'days' => 3, 'countries' => ['FR'],
            'bands' => [500 => 490, 2000 => 790]],
        ['service' => 'shop', 'label' => 'Retrait en boutique', 'to_pickup_point' => true,
            'bands' => [30000 => 0]],
    ],
    'pickup_points' => [
        ['id' => 'shop', 'name' => 'La boutique', 'street' => ['1 rue de Rivoli'], 'postcode' => '75001',
            'city' => 'Paris', 'country' => 'FR', 'opening_hours' => [2 => [['10:00', '19:00']], 6 => [['10:00', '13:00']]]],
    ],
]);

$shipment = new Shipment(
    new Address('Glitch Art', ['12 rue de la Paix'], '75002', 'Paris', 'FR'),
    new Address('Ada Lovelace', ['3 place Bellecour'], '69002', 'Lyon', 'FR'),
    [new Parcel(450)],
    reference: 'CMD-1042',
    options: ['tracking_number' => '6A12345678901'],
);

foreach ($offline->rate($shipment) as $rate) {
    printf("%-6s %-20s %5.2f %s\n", $rate->service, $rate->label, $rate->amount / 100, $rate->currency);
}

$label = $offline->ship($shipment);
echo $label->trackingNumber, ' ', $label->trackingUrl, "\n";
echo $offline->track($label->trackingNumber)->status->value, "\n";

foreach ($offline->pickupPoints($shipment->recipient) as $point) {
    echo $point->id, ' ', $point->name, ', ', $point->address->city, "\n";
}
```

Output:

```text
shop   Retrait en boutique   0.00 EUR
letter Lettre suivie         4.90 EUR
6A12345678901 https://www.laposte.fr/outils/suivre-vos-envois?code=6A12345678901
unknown
shop La boutique, Paris
```

In Symfony:

```yaml
omnibus:
    gateways:
        retrait:
            factory: offline
            options:
                tracking_url: 'https://www.laposte.fr/outils/suivre-vos-envois?code={number}'
                rates:
                    - { service: shop, label: 'Retrait en boutique', to_pickup_point: true, bands: { 30000: 0 } }
                pickup_points:
                    - { id: shop, name: 'La boutique', street: ['1 rue de Rivoli'], postcode: '75001', city: Paris, country: FR,
                        opening_hours: { 2: [['10:00', '19:00']] } }
```

## Errors

The gateway calls no carrier, so it raises no `CarrierException`. `GetSlip`, `Cancel` and
`Paperless`, and `Rating` without `rates`, raise `RequestNotSupportedException`. A
`pickup_points` entry without `id` or `name` is not refused: PHP warns of the undefined key and the value is an empty string.

## Verified, and not

Tested: `Tests/OfflineGatewayTest.php` (the typed tracking number and its URL-encoded link, no
document, the `UNKNOWN` status, no `GetSlip`, the configured counter returned for an address in
another city with its opening hours) and `Tests/OfflineContractTest.php`, the family's conformance
suite (the failure checks are skipped: there is no carrier to fail). The example above was run in
the core's harness. There is nothing to verify live.

## Responsibility

No third-party service is called. The tracking link points wherever `tracking_url` says; the
carrier's or the post office's terms apply to its page.
