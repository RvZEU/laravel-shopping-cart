<?php

namespace Melihovv\ShoppingCart;

use Illuminate\Support\Collection;
use Melihovv\ShoppingCart\Coupons\Coupon;
use Melihovv\ShoppingCart\Repositories\ShoppingCartRepositoryInterface;

class ShoppingCart
{
    /**
     * Default instance name.
     */
    const DEFAULT_INSTANCE_NAME = 'default';

    /**
     * Current instance name.
     *
     * User can several instances of the cart. For example, regular shopping
     * cart, wishlist, etc.
     *
     * @var string
     */
    private $instanceName;

    /**
     * Repository for cart store.
     *
     * @var ShoppingCartRepositoryInterface
     */
    private $repo;

    /**
     * Shopping cart content.
     *
     * @var Collection
     */
    private $content;

    /**
     * Coupons.
     *
     * @var Collection
     */
    private $coupons;

    /**
     * Shipping.
     *
     * @var float
     */
    private $shipping;

    /**
     * Additional Shipping Tax.
     *
     * @var float
     */
    private $shipping_tax;

    /**
     * Additional Shipping.
     *
     * @var float
     */
    private $additional_shipping;

    /**
     * Shipping Tax.
     *
     * @var float
     */
    private $additional_shipping_tax;


    /**
     * Shipping method.
     *
     * @var string
     */
    private $shipping_method;


    private $signature;

    /**
     * ShoppingCart constructor.
     *
     * @param ShoppingCartRepositoryInterface $repo
     */
    public function __construct(ShoppingCartRepositoryInterface $repo)
    {
        $this->repo = $repo;
        $this->instance(self::DEFAULT_INSTANCE_NAME);
        $this->content = new Collection();
        $this->coupons = new Collection();
    }

    /**
     * Add an item to the shopping cart.
     *
     * If an item is already in the shopping cart then we simply update its
     * quantity.
     *
     * @param string|int $id
     * @param string     $name
     * @param int|float  $price
     * @param int        $quantity
     * @param array      $options
     *
     * @return CartItem
     */
    public function add($id, $name, $price, $quantity, $options = [])
    {
        $cartItem = new CartItem($id, $name, $price, $quantity, $options);
        $uniqueId = $cartItem->getUniqueId();

        if ($this->content->has($uniqueId)) {
            $cartItem->quantity += $this->content->get($uniqueId)->quantity;
        }

        $this->content->put($uniqueId, $cartItem);

        return $cartItem;
    }

    public function update($id, $name, $price, $quantity, $options = [])
    {

        $cartItem = new CartItem($id, $name, $price, $quantity, $options);

        $uniqueId = $cartItem->getUniqueId();
        if ($this->content->has($uniqueId)) {
            $cartItem->quantity = $quantity;
        }
        $this->content->where('id','=',13)->first();
        $this->content->put($uniqueId, $cartItem);

        return $cartItem;
    }

    /**
     * Remove the item with the specified unique id from shopping cart.
     *
     * @param string|int $uniqueId
     *
     * @return bool
     */
    public function remove($id)
    {
        $cartItem = $this->content->where('id','=',$id)->first();

        if(!$cartItem){
            return false;
        }
        $uniqueId = $cartItem->getUniqueId();
        if ($cartItem = $this->get($id)) {
            $this->content->pull($cartItem->getUniqueId());

            return true;
        }

        return false;
    }

    /**
     * Check if an item with specified unique id is in shopping cart.
     *
     * @param string|int $uniqueId
     *
     * @return bool
     */
    public function has($uniqueId)
    {
        return $this->content->has($uniqueId);
    }

    /**
     * Get the item with the specified unique id from shopping cart.
     *
     * @param string|int $id
     *
     * @return CartItem|null
     */
    public function get($id)
    {
        $cartItem = $this->content->where('id','=',$id)->first();
        if(!$cartItem){
            return null;
        }
        return $cartItem;
    }


    public function getShipping(){
       return $this->shipping;
    }

    public function getShippingTax(){
       return $this->shipping_tax;
    }



    public function setShipping($cost,$method){
        $this->shipping_method = $method;
        if($cost > 0) {
            $this->shipping = $cost;
            $this->shipping_tax = $cost - ($cost / 1.21);
        }else{
            $this->shipping = 0;
            $this->shipping_tax = 0;
        }
    }

    public function setAdditionalShipping($cost){

        if($cost > 0) {
            $this->additional_shipping = $cost;
            $this->additional_shipping_tax = $cost - ($cost / 1.21);
        }else{
            $this->additional_shipping = 0;
            $this->additional_shipping_tax = 0;
        }
    }

    public function getAdditionalShipping(){
        return $this->additional_shipping;
    }

    /**
     * Get shopping cart content.
     *
     * @return Collection
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * Get the quantity of the cart item with specified unique id.
     *
     * @param $uniqueId
     * @param $quantity
     *
     * @return bool
     */
    public function setQuantity($uniqueId, $quantity)
    {
        if ($cartItem = $this->get($uniqueId)) {
            $cartItem->quantity = $quantity;

            $this->content->put($cartItem->getUniqueId(), $cartItem);

            return true;
        }

        return false;
    }

    /**
     * Clear shopping cart.
     */
    public function clear()
    {
        $this->content = new Collection();
    }

    /**
     * Get the number of item in the shopping cart.
     *
     * @return int
     */
    public function count()
    {
        return $this->content->count();
    }

    /**
     * Get the number of item in the shopping cart.
     *
     * @return int
     */
    public function countTotalItems()
    {
        return $this->content->sum(function (CartItem $cartItem) {
            return $cartItem->getQuantity();
        });
    }


    /**
     * Get subtotal price without coupons.
     *
     * @return float
     */
    public function getSubTotal()
    {
        return $this->content->sum(function (CartItem $cartItem) {
            return $cartItem->getTotal();
        });
    }

    /**
     * Get total price without coupons.
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->content->sum(function (CartItem $cartItem) {
            return $cartItem->getTotal() + $this->shipping + $this->additional_shipping;
        });
    }

    /**
     * Get total tax with coupons.
     *
     * @return float
     */
    public function getTax()
    {
        return $this->content->sum(function (CartItem $cartItem) {
            return $cartItem->getTax() + $this->shipping_tax + $this->additional_shipping_tax;
        });
    }


    public function getTotalShipping(){


        return $this->shipping + $this->additional_shipping;

    }

    /**
     * Get total price with coupons.
     *
     * @return float
     */
    public function getTotalWithCoupons()
    {
        $total = $this->getTotal();
        $totalWithCoupons = $total;

        $this->coupons->each(function (Coupon $coupon) use ($total, &$totalWithCoupons) {
            /**
             * @var Coupon $coupon
             */
            $totalWithCoupons -= $coupon->apply($total);
        });

        return $totalWithCoupons;
    }

    /**
     * Add coupon.
     *
     * @param Coupon $coupon
     */
    public function addCoupon(Coupon $coupon)
    {
        $this->coupons->push($coupon);
    }

    /**
     * Get coupons.
     *
     * @return Collection
     */
    public function coupons()
    {
        return $this->coupons;
    }

    /**
     * Set shopping cart instance name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function instance($name)
    {
        $name = $name ?: self::DEFAULT_INSTANCE_NAME;
        $name = str_replace('shopping-cart.', '', $name);

        $this->instanceName = sprintf('%s.%s', 'shopping-cart', $name);

        return $this;
    }

    /**
     * Get current shopping cart instance name.
     *
     * @return string
     */
    public function currentInstance()
    {
        return $this->instanceName;
    }

    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * @param mixed $signature
     */
    public function setSignature($signature): void
    {
        $this->signature = $signature;
    }



    /**
     * Store the current instance of the cart.
     *
     * @param $id
     *
     * @return $this
     */
    public function store($id)
    {
        $this->repo->createOrUpdate(
            $id,
            $this->instanceName,
            json_encode(serialize([
                'content' => $this->content,
                'coupons' => $this->coupons,
                'shipping' => $this->shipping,
                'shipping_tax' => $this->shipping_tax,
                'additional_shipping' => $this->additional_shipping,
                'additional_shipping_tax' => $this->additional_shipping_tax,
                'shipping_method' => $this->shipping_method,
                'signature' => $this->signature
            ]))
        );

        return $this;
    }

    /**
     * Store the specified instance of the cart.
     *
     * @param $id
     *
     * @return $this
     */
    public function restore($id)
    {
        $cart = $this->repo->findByIdAndInstanceName($id, $this->instanceName);

        if ($cart === null) {
            return;
        }

        $unserialized = unserialize(json_decode($cart->content));
        $this->content = $unserialized['content'];
        $this->coupons = $unserialized['coupons'];
        $this->shipping = $unserialized['shipping'] ?? 0;
        $this->shipping_tax = $unserialized['shipping_tax'] ?? 0;
        $this->additional_shipping = $unserialized['additional_shipping'] ?? 0;
        $this->additional_shipping_tax = $unserialized['additional_shipping_tax'] ?? 0;
        $this->shipping_method = $unserialized['shipping_method'] ?? 0;
        $this->signature = $unserialized['signature'] ?? false;
        $this->instance($cart->instance);

        return $this;
    }

    /**
     * Delete current shopping cart instance from storage.
     *
     * @param $id
     */
    public function destroy($id)
    {
        $this->repo->remove($id, $this->instanceName);
    }

    /**
     * @return string
     */
    public function getShippingMethod(): string
    {
        return $this->shipping_method;
    }

    /**
     * @param string $shipping_method
     */
    public function setShippingMethod(string $shipping_method): void
    {
        $this->shipping_method = $shipping_method;
    }


}
