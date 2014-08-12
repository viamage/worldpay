<?php namespace PhilipBrown\WorldPay;

use StdClass;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Request {

  /**
   * @var PhilipBrown\WorldPay\Environment
   */
  private $environment;

  /**
   * @var PhilipBrown\WorldPay\InstId
   */
  private $instId;

  /**
   * @var PhilipBrown\WorldPay\CartId
   */
  private $cartId;

  /**
   * @var PhilipBrown\WorldPay\Secret
   */
  private $secret;

  /**
   * @var PhilipBrown\WorldPay\Money
   */
  private $money;

  /**
   * @var PhilipBrown\WorldPay\Currency
   */
  private $currency;

  /**
   * @var PhilipBrown\WorldPay\Route
   */
  private $route;

  /**
   * @var array
   */
  private $parameters;

  /**
   * @var array
   */
  private $defaultSignatureFields = ['instId', 'cartId', 'currency', 'amount'];

  /**
   * Create a new Request
   *
   * @param PhilipBrown\WorldPay\Environment $environment
   * @param PhilipBrown\WorldPay\InstId $instId
   * @param PhilipBrown\WorldPay\CartId $cartId
   * @param PhilipBrown\WorldPay\Secret $secret
   * @param PhilipBrown\WorldPay\Money $amount
   * @param PhilipBrown\WorldPay\Currency $currency
   * @param PhilipBrown\WorldPay\Route $route
   * @param array $parameters
   * @return void
   */
  public function __construct(
    Environment $environment,
    InstId $instId,
    CartId $cartId,
    Secret $secret,
    Money $amount,
    Currency $currency,
    Route $route,
    array $parameters = []
  )
  {
    $this->environment = $environment;
    $this->instId = $instId;
    $this->cartId = $cartId;
    $this->secret = $secret;
    $this->amount = $amount;
    $this->currency = $currency;
    $this->parameters = $parameters;
    $this->route = $route;
  }

  /**
   * Set the signature fields to use in the signature hash
   *
   * @param array $fields
   * @return PhilipBrown\WorldPay\Request
   */
  public function setSignatureFields(array $fields)
  {
    $this->defaultSignatureFields = array_merge($this->defaultSignatureFields, $fields);

    return $this;
  }

  /**
   * Send the request to WorldPay
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function send()
  {
    $request = $this->prepare();

    $url = $request->route.'?signature='.$request->signature.'&'.http_build_query($request->data);

    return RedirectResponse::create($url)->send();
  }

  /**
   * Return an object containing the request
   *
   * @return StdClass
   */
  public function prepare()
  {
    return new Body(
      (string) $this->route,
      $this->generateSignature(),
      $this->getTheRequestParameters()
    );
  }

  /**
   * Check to see if we are in a default environment
   *
   * @return bool
   */
  private function isDefaultEnvironment()
  {
    return in_array((string) $this->environment, ['production', 'development']);
  }

  /**
   * Generate the signature
   *
   * @return string
   */
  private function generateSignature()
  {
    $defaults = [
      'instId'    => $this->instId,
      'cartId'    => $this->cartId,
      'currency'  => $this->currency,
      'amount'    => $this->amount
    ];

    $parameters = array_intersect_key($this->parameters, array_flip($this->defaultSignatureFields));

    return md5((string) $this->secret.':'.implode(':', array_merge($defaults, $parameters)));
  }

  /**
   * Get the request parameters
   *
   * @return array
   */
  private function getTheRequestParameters()
  {
    return array_merge([
      'instId'    => (string) $this->instId,
      'cartId'    => (string) $this->cartId,
      'currency'  => (string) $this->currency,
      'amount'    => (string) $this->amount,
      'testMode'  => $this->environment->asInt()
      ],
      $this->parameters
    );
  }

}