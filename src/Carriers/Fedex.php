<?php

namespace Hunghbm\FedEx\Carriers;

use Config;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shipping\Carriers\AbstractShipping;

/**
 * Class Rate.
 *
 */
class Fedex extends AbstractShipping
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'fedexrate';

    /**
     * Returns rate for fedexrate
     *
     * @return array
     */
    public function calculate($service = null)
    {
        $data = ['status' => false];
        if (! $this->isAvailable()) {
            $data['message'] = 'Unavailable shipping method';
            return $data;
        }

        $cart = Cart::getCart();

        if (empty($cart->shipping_address)) {
            $data['message'] = 'No shipping address';
            return $data;
        }
        // Get shipping address
        $address = $cart->shipping_address;

        $shipperStreetLines = core()->getConfigData('sales.shipping.origin.address1') ?? '';
        $shipperCity = core()->getConfigData('sales.shipping.origin.city') ?? '';
        $shipperState = core()->getConfigData('sales.shipping.origin.state') ?? '';
        $shipperZipCode = core()->getConfigData('sales.shipping.origin.zipcode') ?? '';
        $shipperCountryCode = core()->getConfigData('sales.shipping.origin.country') ?? '';

        if (empty($shipperZipCode) || empty($shipperCountryCode)) {
            $data['message'] = 'No Zip/Postal code or Country in dashboard configuration';
            return $data;
        }

        $shippingInfo = [
            'shipper' => [
                'street_lines' => $shipperStreetLines,
                'city' => $shipperCity,
                'state' => $shipperState,
                'postal_code' => $shipperZipCode,
                'country_code' => $shipperCountryCode,
            ],
            'recipient' => [
                'street_lines' => ! empty($address->address1)
                    ? $address->address1 : (! empty($address->address2) ? $address->address2 : ''),
                'city' => ! empty($address->city) ? $address->city : '',
                'state' => ! empty($address->state) ? $address->state : '',
                'postal_code' => ! empty($address->postcode) ? $address->postcode : '',
                'country_code' => ! empty($address->country) ? $address->country : '',
            ],
        ];

        if ($this->getConfigData('type') == 'per_unit') {
            foreach ($cart->items as $item) {
                if ($item->product->getTypeInstance()->isStockable()) {
                    $shipping = $item->child->product->product ?? null;
                    if (!$shipping) {
                        $shipping = $item->product;
                    }

                    $weight = ! empty($shipping->weight) ? $shipping->weight * $item->quantity : 0;
                    $length = ! empty($shipping->depth) ? $shipping->depth * $item->quantity : 0;
                    $width = ! empty($shipping->width) ? $shipping->width * $item->quantity : 0;
                    $height = ! empty($shipping->height) ? $shipping->height * $item->quantity : 0;
                }
            }

            $amount = $this->getConfigData('default_rate') * $item->quantity;
            $shippingInfo['package'] = [
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
            ];
            $shippingInfo['packaging_type'] = $this->getConfigData('packaging_type');
            $rateDetails = calculateShippingRates($shippingInfo);

            if (empty($rateDetails['status'])) {
                return $rateDetails;
            }

            $services = [];
            foreach ($rateDetails as $key => $rateDetail) {
                if (! empty($rateDetail['type']) && in_array($rateDetail['type'], $service)) {
                    $object = new CartShippingRate;
                    $object->carrier = 'fedexrate';
                    $object->carrier_title = $this->getConfigData('title');
                    $object->method = 'fedexrate_' . ($rateDetail['type'] ?? '');
                    $object->method_title = $rateDetail['name'] ?? '';
                    $object->method_description = ($rateDetail['name'] ?? '') . ' - ' . $rateDetail['astra'];
                    $object->price = 0;
                    $object->base_price = 0;
                    $amount = $rateDetail['amount'] ?? $amount;
                    $object->price += core()->convertPrice($amount);
                    $object->base_price += $amount;
                    $services[] = $object;
                    unset($object);
                }
            }
        } else {
            $object->price = core()->convertPrice($this->getConfigData('default_rate'));
            $object->base_price = $this->getConfigData('default_rate');
        }
        $data['status'] = true;
        $data['services'] = $services;

        return $data;
    }
}
