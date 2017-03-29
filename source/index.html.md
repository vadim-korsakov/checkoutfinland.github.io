---
title: Checkout API Reference

language_tabs:
  - code

toc_footers:
#  - <a href='#'>Sign Up for a Developer Key</a>
#  - <a href='https://github.com/tripit/slate'>Documentation Powered by Slate</a>

includes:

search: true
---

# Introduction

The following document describes the Checkout Finlands general public API in the context of payment buttons and making payments through the API.

To get started with the API, get in touch with Checkout Finland's customer support and you will be directed into the right direction from there. API is available for use for our merchants and partners.

# Testing

> Example class implementation that will be used in the later examples

```php
class Checkout
{
	private $version = "0001";
	private $language	= "FI";
	private $country = "FIN";
	private $currency	= "EUR";
	private $device	= "1";
	private $content = "1";
	private $type	= "0";
	private $algorithm = "3";
	private $merchant	= "";
	private $password	= "";
	private $stamp = 0;
	private $amount	= 0;
	private $reference = "";
	private $message = "";
	private $return	= "";
	private $cancel	= "";
	private $reject	= "";
	private $delayed = "";
	private $delivery_date = "";
	private $firstname = "";
	private $familyname	= "";
	private $address = "";
	private $postcode	= "";
	private $postoffice	= "";
	private $status	= "";
	private $email = "";

	public function __construct($merchant, $password)
	{
		$this->merchant	= $merchant; // merchant id
		$this->password	= $password; // security key (about 80 chars)
	}

	/*
 	 * generates MAC and prepares values for creating payment
	 */
	public function getCheckoutObject($data)
	{
		// overwrite default values
		foreach($data as $key => $value)
		{
			$this->{$key} = $value;
		}

		$mac = strtoupper(md5("{$this->version}+{$this->stamp}+{$this->amount}+{$this->reference}+{$this->message}+{$this->language}+{$this->merchant}+{$this->return}+{$this->cancel}+{$this->reject}+{$this->delayed}+{$this->country}+{$this->currency}+{$this->device}+{$this->content}+{$this->type}+{$this->algorithm}+{$this->delivery_date}+{$this->firstname}+{$this->familyname}+{$this->address}+{$this->postcode}+{$this->postoffice}+{$this->password}"));
		$post['VERSION'] = $this->version;
		$post['STAMP'] = $this->stamp;
		$post['AMOUNT']	= $this->amount;
		$post['REFERENCE'] = $this->reference;
		$post['MESSAGE'] = $this->message;
		$post['LANGUAGE']	= $this->language;
		$post['MERCHANT']	= $this->merchant;
		$post['RETURN']	= $this->return;
		$post['CANCEL']	= $this->cancel;
		$post['REJECT']	= $this->reject;
		$post['DELAYED'] = $this->delayed;
		$post['COUNTRY'] = $this->country;
		$post['CURRENCY']	= $this->currency;
		$post['DEVICE']	= $this->device;
		$post['CONTENT'] = $this->content;
		$post['TYPE']	= $this->type;
		$post['ALGORITHM'] = $this->algorithm;
		$post['DELIVERY_DATE'] = $this->delivery_date;
		$post['FIRSTNAME'] = $this->firstname;
		$post['FAMILYNAME'] = $this->familyname;
		$post['ADDRESS'] = $this->address;
		$post['POSTCODE'] = $this->postcode;
		$post['POSTOFFICE'] = $this->postoffice;
		$post['MAC'] = $mac;

		$post['EMAIL'] = $this->email;
		$post['PHONE'] = $this->phone;

		return $post;
	}

	/*
	 * returns payment information in XML
	 */
	public function getCheckoutXML($data)
	{
		$this->device = "10";
		return $this->sendPost($this->getCheckoutObject($data));
	}

	private function sendPost($post) {
		$options = array(
				CURLOPT_POST 		=> 1,
				CURLOPT_HEADER 		=> 0,
				CURLOPT_URL 		=> 'https://payment.checkout.fi',
				CURLOPT_FRESH_CONNECT 	=> 1,
				CURLOPT_RETURNTRANSFER 	=> 1,
				CURLOPT_FORBID_REUSE 	=> 1,
				CURLOPT_TIMEOUT 	=> 20,
				CURLOPT_POSTFIELDS 	=> http_build_query($post)
		);

		$ch = curl_init();
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
	    curl_close($ch);

	    return $result;
	}

	public function validateCheckout($data)
	{
		$generatedMac =  strtoupper(hash_hmac("sha256","{$data['VERSION']}&{$data['STAMP']}&{$data['REFERENCE']}&{$data['PAYMENT']}&{$data['STATUS']}&{$data['ALGORITHM']}",$this->password));

		if($data['MAC'] === $generatedMac)
			return true;
		else
			return false;
	}

	public function isPaid($status)
	{
		if(in_array($status, array(2, 4, 5, 6, 7, 8, 9, 10)))
			return true;
		else
			return false;
	}
}  // class Checkout
```

Test credentials

* Merchant Id (MERCHANT): `375917`
* Secret Key: `SAIPPUAKAUPPIAS`

Please note that not all payment methods support testing. The payment methods are enabled that support testing payments. There might be some features that are not working as they are intended to work in the testing side due to limitations to external integrations.

The whole test code is available at: [http://demo1.checkout.fi/xml2.txt](http://demo1.checkout.fi/xml2.txt).

# General flow

## Examples

### Payment process

The following illustrates how the user moves in the payment process.

![flow-diagram](images/flow.png)

1. Buyer goes into Checkout.fi
2. Chooses payment method
3. Paid for the purchase
4. Returns automatically to the merchant with the information of successful payment
5. If for some reason the buyer did not return to the merchant, Checkout queries the payment method provider for the information of the payment
6. Receive information from the payment method provider that the payment went through correctly
7. Inform merchant of the successful payment.
8. Merchant queries the payment status from Checkout
9. Checkout replies with the status

# Tokenization API

## Card addition (tokenization)

1. Use the Solinor card-addition described in the [Solinor "STORE A CARD" example](https://paymenthighway.fi/dev/#examples) to fetch the Solinor-`card_token`.
2. Migrate the Solinor-`card_token` to a Checkout-`card_token` using the [Token migration API](http://localhost:4567/#token-migration) described below.
3. Use the Checkout-`card_token` for making payments.

## Token migration

Will convert an external token (for example Solinor token) to a Checkout-token, which can then be used for making payments in Checkout Finland Payment API.

If token has already been migrated earlier, statusText will be 'TOKEN ALREADY MIGRATED' and Checkout-token will be returned.

```req
POST /token/migrate HTTP/1.1
Host: payment.checkout.fi
Content-Type: application/x-www-form-urlencoded
Cache-Control: no-cache

merchant=375917&provider_token=1296b3bc-407b-439b-9afd-c138e3ababa3
```

### HTTP Request

`POST https://payment.checkout.fi/token/migrate`

Body field | Type | Description
-------------- | -------------- | --------------
provider_token | UUID4 | External (e.g. Solinor) token
merchant | N | Merchant ID given by Checkout

```xml
<?xml version="1.0" encoding="utf-8"?>
<response>
    <statusCode>200</statusCode>
    <statusText>TOKEN MIGRATE OK</statusText>
    <token>4c9705fd-c31d-4d2f-accf-f07b63eb80fe</token>
</response>
```

### HTTP Response

Body field | Type | Description
-------------- | -------------- | --------------
token | UUID4 | Checkout-token
statusCode | SCODE | Checkout status code, described in table below
statusText | AN | status message, e.g. 'TOKEN MIGRATE OK', 'TOKEN ALREADY MIGRATED'

#### Status codes

Status Code | Description
---- | -----------
200 | Token was migrated successfully
201 | Token has already been migrated, will return existing checkout-token
400 | Merchant with given ID not found
500 | General internal server error

## Token removal

Will remove a card tokenization from Checkouts system.

### HTTP Request

`DELETE /tokenization/<token>`

where `<token>` is Checkout-card-token

### HTTP Response

Body field | Type | Description
-------------- | -------------- | --------------
statusCode | SCODE | 200 if card removed
statusText | AN | status message, e.g. 'card removed'

## Tokenized card info fetch

Will return info about the credit card if token is valid.

### HTTP Request

`GET /tokenization/<token>`

where `<token>` is Checkout-card-token

### HTTP Response

Body field | Type | Description
-------------- | -------------- | --------------
card.type | AN | credit card type, e.g. "Visa",
card.partialPan | AN | last 4 digits of credit card, e.g. "0024",
card.expireYear | AN | credit card expiration year, e.g. "2017",
card.expireMonth | AN | credit card expiration month, e.g. "03"
statusCode | SCODE | 200 if card exists and info is fetched, 404 if card token not found
statusText | AN | status message, e.g. 'card info fetced'


# Payment APIs

## Payment

### URL

* `POST`: https://payment.checkout.fi

### Field descriptions

> Example uses the earlier created class (in testing stage)

```php
// Order information
$coData	= array();
$coData["stamp"] = time(); // unique timestamp
$coData["reference"] = "12344";
$coData["message"] = "Furniture materials\nSome wood, dust and magic fairies";
$coData["return"] = "http://demo1.checkout.fi/xml2.php?test=1";
$coData["delayed"] = "http://demo1.checkout.fi/xml2.php?test=2";
$coData["amount"] = "1000"; // price in cents
$coData["delivery_date"] = date("Ymd");
$coData["firstname"] = "Matti";
$coData["familyname"] = "Meikäläinen";
$coData["address"] = "Ääkköstie 5b3\nKulmaravintolan yläkerta";
$coData["postcode"] = "33100";
$coData["postoffice"] = "Tampere";
$coData["email"] = "support@checkout.fi";
$coData["phone"] = "0800 552 010";

// coObject for old method
$coObject = $co->getCheckoutObject($coData);
// change stamp for xml method so that the new call has unique stamp compared to the previous
$coData['stamp'] = time() + 1;
$response =	$co->getCheckoutXML($coData); // get payment button data
$xml = simplexml_load_string($response);
```

| #  | Description | Name | Value | Format | Required |
|----|-------------|------|-------|--------|----------|
| 1 | Payment version. Always "0001". | VERSION | "0001" | AN 4 | Yes |
| 2 | Unique identifier for the payment in the context of the merchant. Has to be unique. | STAMP | unique_id | AN 20 | Yes |
| 3 | Payment amount in cents. | AMOUNT | 0 | N 8 | Yes |
| 4 | Payment reference number for the payment from the merchant. | REFERENCE | standard_reference | AN 20 | Yes |
| 5 | Payment message from the Merchant to the buyer | MESSAGE | "Hi, thanks for shopping." | AN 1000 | No |
| 6 | Payment language. Options currently are: "FI", "EN", "SE". | LANGUAGE | "FI" | AN 2 | No |
| 7 | Merchant Id. Given identifier for the merchant (you get this from Checkout Finland), for testing: 375917 | MERCHANT | 375917 | AN 20 | Yes |
| 8 | Return callback. Called when the payment is successfully paid. | RETURN | | AN 300 | Yes |
| 9 | Cancel callback. Called when the payment is cancelled for some reason. | CANCEL | | AN 300 | Yes |
| 10 | Reject callback. Called when the payment is rejected. If not defined, on reject the cancel will be called. | REJECT | | AN 300 | No |
| 11 | Delayed callback. Called if the payment is delayed. | DELAYED | | AN 300 | No |
| 12 | Country, ISO-3166-1 alpha-3. | COUNTRY | "FIN" | AN 3 | No |
| 13 | Valuutta. Currently always EUR. | CURRENCY | "EUR" | AN 3 | Yes |
| 14 | Device type. `1 = HTML`, `10 = XML`. | DEVICE | 1 | N 2 | Yes |
| 15 | Content of the purchase. `1 = Normal`, `2 = adult industry` | CONTENT | 1 | N 2 | Yes |
| 16 | Payment types. | TYPE | 0 | N 1 | Yes |
| 17 | Checksum calculation algorithm. Use 3 commonly. | ALGORITHM | 3 | N 1 | Yes |
| 18 | Delivery date. Format: YYYYMMDD.  | DELIVERY_DATE | 20171231 | N 8 | Yes |
| 19 | Required when using loaning services. First name of the buyer. | FIRSTNAME | "Matti" | AN 40 | No |
| 20 | Required when using loaning services. Last name of the buyer. | FAMILYNAME | "Meikäläinen" | AN 40 | No |
| 21 | Required when using loaning services. Delivery address. | ADDRESS | "Maantie 123" | AN 40 | No |
| 22 | Required when using loaning services. Delivery post number. | POSTCODE | "12345" | AN 14 | No |
| 23 | Required when using loaning services. Delivery postal office. | POSTOFFICE | "" | AN 18 | No |
| 24 | Checksum is calculated by combining fields 1-23 and the secret key, separating them with a `+`-sign  | MAC |  | AN 32 | Yes |
| 25 | Buyer email. | EMAIL | | AN 200 | No |
| 26 | Buyer phone number. | PHONE | | AN 30 | No |

*Field 14*: If you want to use Checkouts payment wall, use HTML device type (end point). If the payment buttons are getting integrated as part of merchants online service, use XML. Checkout recommends using XML as it allows more tight integration and customization. HTTP POST is done by the integrating service and Checkout replies with XML payment wall that the service then can open and manipulate to render the payment wall.

*Field 17*: `3 == sha256 and md5`. Payment is created with MD5 and the return checksum is calculated with SHA256.

*Field 18*: Best estimate of the delivery date of the product. If no date is known, use the latest possible date. Products that require loan are sent to the buyer by the delivery date.

### Calculating the checksum

When calculating the checksum use the fields 1 to 23 and the security key as the last field. The fields are separated from each other with `+` -sign. If a field is empty, use empty string in the calculation. The calculated checksum needs to be transferred in capitalized characters. Check is done with the method defined in field 17.

> Example of calculating the MD5

```php
MD5(
  VERSION+STAMP+AMOUNT+REFERENCE+MESSAGE+
  LANGUAGE+MERCHANT+RETURN+CANCEL+REJECT+
  DELAYED+COUNTRY+CURRENCY+DEVICE+CONTENT+
  TYPE+ALGORITHM+DELIVERY_DATE+FIRSTNAME+
  FAMILYNAME+ADDRESS+POSTCODE+POSTOFFICE+SECRET_KEY
)
```

### Response

The response will arrive with the users browser, if the user returns to the service. If the for some reason this does not happen, Checkout will send a HTTP GET -request to the same address the browser was supposed to be redirected to, the service has to accept the incoming HTTP GET from Checkout even though it might lack cookies or a session. The message will be checked with MAC -check.

Additional information on how to do the check can be found from the example file:  [http://demo1.checkout.fi/xml2.txt](http://demo1.checkout.fi/xml2.txt).

| # | Description | Field | Value |
|---|-------------|-------|-------|
| 1 | Payment version. Always "0001". | VERSION | "0001" |
| 2 | Unique identifier for the payment in the context of the merchant. Has to be unique. | STAMP | unique_id |
| 3 | Payment reference number for the payment from the merchant. | REFERENCE | standard_reference |
| 4 | Payment archive id | PAYMENT | Checkouts unique ID for the payment. |
| 5 | Payment status | STATUS | 2 = success | 
| 6 | Used algorithm. Use 3. | ALGORTH | 3 |
| 7 | MAC check | MAC |

Responses checksum is calculated using HMAC SHA256 the following way:

`HASH_HMAC_SHA256(SECURITY_KEY, "VERSION&STAMP&REFERENCE&PAYMENT&STATUS&ALGORITHM")`

Field separator is the `&`-sign.

> Example of implementing the check from the demo1.checkout.fi/xml2.txt

```php
$generatedMac=strtoupper(hash_hmac("sha256","{$data['VERSION']}&{$data['STAMP']}&{$data['REFERENCE']}&{$data['PAYMENT']}&{$data['STATUS']}&{$data['ALGORITHM']}",$this->password));
```


### Payment Statuses

| Status | Description |
|---------------|-------------|
| -10 | Payment refunded to the buyer |
| -4 | Payment cannot be found |
| -3 | Payment timeout |
| -2 | Payment cancelled by Checkout or payment method provider |
| -1 | Payment cancelled by buyer |
| 1 | Payment pending |
| 2 | Payment approved |
| 3 | Payment delayed |
| 4 | This status is saved for future additions. Works like status 3. |
| 5 | This status is saved for future additions. Approved payment. |
| 6 | Payment frozen |
| 7 | Payment method provider has accepted payment and it required approval |
| 8 | Payment method provider has accepted payment and it has been approved |
| 9 | This status is saved for future additions.|
| 10 | Payment settled |

* Payment has been charged or is approved when the payment is in statuses: 2, 4, 5, 6, 7, 8, 9, 10.
* Retailer or a merchant can approve the order when the status is 2, 5, 6, 7, 8, 9 or 10.

## Shop-in-shop payment

Payments for shop-in-shop customers. Includes "aggregate merchant" (master merchant) and items with submerchants.
Making SiS-payments with single items/submerchants is ok.

If credit card token is sent to PaymentAPI, the API will use tokenized card for payment. If no token is sent, the user will be guided to credit card information input (payment).

Commit can be used with token-payments. If commit is true, the tokenized credit card will be charged. If commit is false, it will make a payment reservation (authorization hold) on the card. This authorization hold will be committed or retracted later through the 'commit payment' or 'retract payment' API's.

```xml
<?xml version="1.0"?>
<!--

    Example of xml used in shop-in-shop payment creation, allthough some elements are marked as not required
    dont just delete them but leave them empty if you dont want to use them.

	  List of different kinds of merchant identifications:
		aggregator 	= aggregator account used to create merchants and payments
		merchant  	= merchant ID, unique for each vendor created by the aggrecator account
		m 			= merchant ID, that will receive the commission from the payment

	<control>
		Holds a list of JSON objects that define the commission:
			<control>[{"a":146,"m”:"12345","d":"commission”}]</control>
				a = sum of commission in cents
				m = merchant ID, that will receive the commission from the payment
				d = message/description of payment



 -->
<checkout xmlns="http://checkout.fi/request">
  <request type="aggregator" test="false">
      <aggregator>375917</aggregator> <!-- shop-in-shop aggregate merchant id  -->
      <version>0002</version>
      <stamp>75646368746654321</stamp> <!-- unique identifier the payment -->
      <reference>12344</reference>
      <description>...</description>
      <device>10</device>
      <content>1</content>
      <type>0</type>
      <algorithm>3</algorithm>
      <currency>EUR</currency> <!-- EUR is the only supported currency at the moment -->
      <items>
          <item>
              <code>1112233an</code> <!-- product id/sku/code, not required -->
              <stamp>98765323776</stamp>
              <description>product 1</description> <!-- required -->
              <price currency="EUR" vat="23">100</price> <!-- vat attribute is not required. Price in cents. -->
              <merchant>375917</merchant> <!-- this is the merchant id of the shop selling this item, required -->
              <control>[{"a":12, "m":"375917", "d":"reward x"},{"a":146,"m”:"375917","d":"commission”}]</control> <!-- example of two commissions being deducted from the price of one item in the purchase  -->
          </item>
          <item>
              <code></code> <!-- product id/sku/code -->
              <description>product 2</description>
              <reference>987654321</reference>
              <price currency="EUR">100</price>
              <merchant>375917</merchant>
              <control /><!-- When control field is empty no provision is collected from this item -->
          </item>
          <item>
              <code></code> <!-- product id/sku/code -->
              <description></description>
              <price currency="EUR" vat="23">100</price>
              <merchant>375917</merchant>
              <control>[{"a":146,"m”:"375917","d":"commission”}]</control><!-- only a singe commission is deducted from this merchant -->
          </item>
          <amount currency="EUR">300</amount> <!-- has to be exact total from sum of the items prices, in cents -->
      </items>
      <buyer>
          <company vatid=""></company> <!-- not required -->
          <firstname></firstname> <!-- not required -->
          <familyname></familyname> <!-- not required -->
          <address><![CDATA[ ]]></address> <!-- not required -->
          <postalcode></postalcode> <!-- not required -->
          <postaloffice></postaloffice> <!-- not required -->
          <country>FIN</country>
          <email></email> <!-- not required -->
          <gsm></gsm> <!-- not required -->
          <language>FI</language>
      </buyer>
      <delivery>
          <date>20110303</date>
          <company vatid=""></company>
          <firstname></firstname>
          <familyname></familyname>
          <address><![CDATA[ ]]></address>
          <postalcode></postalcode>
          <postaloffice></postaloffice>
          <country></country>
          <email></email>
          <gsm></gsm>
          <language></language>
      </delivery>
      <control type="default">
          <return>return.php</return>
          <reject>return.php</reject>
          <cancel>return.php</cancel>
      </control>
  </request>
</checkout>

```

### HTTP Request

* `POST`: https://payment.checkout.fi

| #  | Description | Name | Value | Format | Required |
|----|-------------|------|-------|--------|----------|
| 1 | Payment version. Always "0002". | VERSION | "0002" | AN 4 | Yes |
| 2 | Unique identifier for the payment in the context of the merchant. Has to be unique. | STAMP | unique_id | AN 20 | Yes |
| 3 | Amount in cents. Has to be exact total from sum of the items prices | AMOUNT | 1000 | N | Yes |
| 4 | Payment reference number for the payment from the merchant. | REFERENCE | standard_reference | AN 20 | Yes |
| 5 | Description of payment/purchase | DESCRIPTION | "Item 1#, Item #2..." | AN 1000 | No |
| 6 | Payment language. Options currently are: "FI", "EN", "SE". | LANGUAGE | "FI" | AN 2 | No |
| 7 | Merchant Id of aggregate merchant. Given identifier for the merchant (you get this from Checkout Finland), for testing: 375917 | AGGREGATE | 375917 | AN 20 | Yes |
| 8 | Return callback. Called when the payment is successfully paid. | RETURN | | AN 300 | Yes* |
| 9 | Cancel callback. Called when the payment is cancelled for some reason. | CANCEL | | AN 300 | Yes* |
| 10 | Reject callback. Called when the payment is rejected. If not defined, on reject the cancel will be called. | REJECT | | AN 300 | No |
| 11 | Delayed callback. Called if the payment is delayed. | DELAYED | | AN 300 | No |
| 12 | Country, ISO-3166-1 alpha-3. | COUNTRY | "FIN" | AN 3 | No |
| 13 | Currency. Currently always EUR. | CURRENCY | "EUR" | AN 3 | Yes |
| 14 | Device type. `1 = HTML`, `10 = XML`. | DEVICE | 1 | N 2 | Yes |
| 15 | Content of the purchase. `1 = Normal`, `2 = adult industry` | CONTENT | 1 | N 2 | Yes |
| 16 | Payment types. | TYPE | 0 | N 1 | Yes* |
| 17 | Checksum calculation algorithm. Use 3 commonly. | ALGORITHM | 3 | N 1 | Yes |
| 18 | Delivery date. Format: YYYYMMDD.  | DELIVERY_DATE | 20171231 | N 8 | Yes |
| 19 | Required when using loaning services. First name of the buyer. | FIRSTNAME | "Matti" | AN 40 | No |
| 20 | Required when using loaning services. Last name of the buyer. | FAMILYNAME | "Meikäläinen" | AN 40 | No |
| 21 | Required when using loaning services. Delivery address. | ADDRESS | "Maantie 123" | AN 40 | No |
| 22 | Required when using loaning services. Delivery post number. | POSTCODE | "12345" | AN 14 | No |
| 23 | Required when using loaning services. Delivery postal office. | POSTOFFICE | "" | AN 18 | No |
| 24 | Checksum is calculated by combining fields 1-23 and the secret key, separating them with a `+`-sign  | MAC |  | AN 32 | Yes |
| 25 | SiS-items of purchase | ITEMS | | | Yes
| 26 | Product id/sku/code | ITEM.CODE |  | AN | No
| 27 | Description of payment/purchase | ITEM.DESCRIPTION | "Product #1" | AN | Yes
| 28 | Price of item in cents | ITEM.PRICE | 50 | N | Yes
| 29 | Merchant id of the shop selling the item (SiS-shop) | ITEM.MERCHANT | 585858 | N | Yes
| 30 | Holds a list of JSON objects that define the commission which SiS-merchant will receive. Not required if no commission given to SiS-merchant | ITEM.CONTROL | | [] | Yes
| 31 | Sum of commission in cents | ITEM.CONTROL.a | 48 | N | No
| 32 | Merchant ID, that will receive the commission from the payment (usually same as ITEM.MERCHANT) | ITEM.CONTROL.m | 585858 | N | No
| 33 | Message/description of payment | ITEM.CONTROL.d | "commission for item #1" | AN | No
| 34 | Token of credit card | TOKEN | "f47ac10b-58cc-4372-a567-0e02b2c3d479" | UUID4 | No
| 35 | Commit token payment (if false, makes a reservation) | COMMIT | true/false | BOOL | No
| 36 | Buyer email | EMAIL | | AN 200 | No |
| 37 | Buyer phone number | PHONE | | AN 30 | No |

*Fields 8, 9, 14, 16*: These are required only with non-token -payments.

*Field 24*: For calculating checksum, check above on Payment-API.

### HTTP Response

| #  | Description | Name | Format |
|----|-------------|------|--------|
| 1 | HTTP Status code (200 if payment/reservation successful) | statusCode | SCODE |
| 2 | Status text (e.g. 'payment done') | statusText | AN |

## Revert

Reverts an existing debit-reservation or committed payment. If this is done for a reservation, the reservation is simply removed or lowered. If this is done for a committed payment, it will create a recompensation to the original payer.

### HTTP Request

`POST /payment/<transactionStamp>/retract`

| #  | Description | Name | Value | Format | Required |
|----|-------------|------|-------|--------|----------|
| 1 | Amount of payment reservation to be retracted in cents | AMOUNT | | N | Yes
| 2 | Merchant id of which the initial reservation was done with | MERCHANT | | N | Yes


### HTTP Response

| #  | Description | Name | Format |
|----|-------------|------|--------|
| 1 | HTTP Status code (200 if retract successful, 404 if payment not found) | statusCode | SCODE |
| 2 | Status text (e.g. 'payment retracted') | statusText | AN |

## Commit payment

Commits an existing credit card reservation.

### HTTP Request

`POST /payment/<transactionStamp>/commit`

| #  | Description | Name | Value | Format | Required |
|----|-------------|------|-------|--------|----------|
| 1 | Amount of payment reservation to be committed | AMOUNT | | N | Yes
| 2 | Merchant id of which the initial reservation was done with | MERCHANT | | N | Yes

### HTTP Response

| #  | Description | Name | Format |
|----|-------------|------|--------|
| 1 | HTTP Status code (200 if commit successful, 404 if payment not found) | statusCode | SCODE |
| 2 | Status text (e.g. 'payment committed') | statusText | AN |

# Polling

## URL

* `POST`: https://rpcapi.checkout.fi/poll

## Field descriptions

| # | Description | Field | Value | Format | Required |
|---|-------------|-------|-------|--------|----------|
| 1 | Payment version, always "0001" | VERSION | "0001" | AN 4 | Yes |
| 2 | Unique identifier for the payment in the context of the merchant. Has to be unique. | STAMP | | AN 20 | Yes |
| 3 | Payment reference number for the payment from the merchant. | REFERENCE | | AN 20 | Yes |
| 4 | Payment archive id | PAYMENT | Checkouts unique ID for the payment. | | AN | Yes |
| 5 | Payment amount in cents | AMOUNT | 1000 | N 8 | Yes |
| 6 | Valuutta. Default, always use EUR.  | CURRENCY | "EUR" | AN 3 | Yes |
| 7 | Return message format. Default, always use 1. `1 == xml`. | FORMAT | 1 | N 1 | Yes |
| 8 | Algorithm used to validate. Default, always use 1. `1 == MD5`. | ALGORITHM | 1 | N 1 | Yes |
| 9 | Checksum is calculated by combining fields 1-8 and the secret key, separating them with a `+`-sign | MAC |  | MAC | | AN 32 | Yes |

## Calculating the checksum

> Example of calculating the MD5

```php
MD5(
  VERSION+STAMP+REFERENCE+MERCHANT+
  AMOUNT+CURRENCY+FORMAT+ALGORITHM+SECRET_KEY
)
```

## Response

> Example of a successfull query

```
<?xml version=”1.0”?>
<trade> <status>2</status>
</trade>
```

If the MAC check fails or any of the fields are incorrect or do not correspond to the fields found in checkout the interface will simply return `error`.


# Refund

## URL

* `POST`: https://rpcapi.checkout.fi/refund2

## Field descriptions

> Example of the sent XML that gets base64 encoded

```
<?xml version='1.0'?>
<checkout>
 <identification>
  <merchant>1234</merchant> <!-- merchant id -->
  <stamp>123456</stamp> <!-- message unique identifier -->
 </identification>
 <message>
  <refund>
   <stamp>12345</stamp> <!-- refund payment unique identifier  -->
   <reference>12345</reference> <!-- reference for the payment refund -->
   <amount>1245</amount> <!-- sum in cents -->
   <receiver>
     <email>email@osoi.te</email>
   </receiver>
  </refund>
 </message>
</checkout>
```

> Example of calculating the MD5

```php
$messageMac=strtoupper(hash_hmac("sha256", base64_encode($message), $secretKey));
```

| # | Description | Field | Value | Format | Required |
|---|-------------|-------|-------|--------|----------|
| 1 | Contains the base64 encoded XML. | DATA | | AN | Yes |
| 2 | Mac checksum of the data. | MAC | | AN | Yes |



> Example responses

```
<?xml version='1.0'?>
<checkout>
 <response>
  <stamp>12345</stamp>
  <statusMessage>REFUNDED</statusMessage>
  <statusCode>2100</statusCode>


  <statusMessage>CHECKSUM MISMATCH</statusMessage>
  <statusCode>1200</statusCode>


  <statusMessage>DATA FIELD IS MISSING</statusMessage>
  <statusCode>1201</statusCode>


  <statusMessage>MAC FIELD IS MISSING</statusMessage>
  <statusCode>1202</statusCode>


  <statusMessage>DATA NOT BASE64 ENCODED STRING</statusMessage>
  <statusCode>1203</statusCode>


  <statusMessage>MAC FORMAT ERROR</statusMessage>
  <statusCode>1204</statusCode>


  <statusMessage>TRADE NOT FOUND</statusMessage>
  <statusCode>2200</statusCode>


  <statusMessage>TRADE ALREADY REFUNDED</statusMessage>
  <statusCode>2201</statusCode>


  <statusMessage>REFUND AMOUNT TOO BIG</statusMessage>
  <statusCode>2202</statusCode>


  <statusMessage>MERCHANT BALANCE TOO LOW</statusMessage>
  <statusCode>2203</statusCode>


  <statusMessage>ACCOUNT NOT ACCEPTED</statusMessage>
  <statusCode>2204</statusCode>


  <statusMessage>REFUND AMOUNT TOO LOW</statusMessage>
  <statusCode>2205</statusCode>


  <statusMessage>GENERIC ERROR</statusMessage>
  <statusCode>2220</statusCode>

  <statusMessage>UNKNOWN ERROR</statusMessage>
  <statusCode>2221</statusCode>

 </response>
</checkout>";
```

# Data types

Type | Format (regex) | Example
-------------- | -------------- | --------------
N | ^[0-9]+$ | 505050
AN | ^[.,’-=/\w;\s]{0,1023}$ | Alph4num3r1c
BOOL | true/false | true
AMOUNT | ^\d{1,12}$ | 9000
CURRENCY | ^(EUR)$ | EUR
UUID4 | ^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$ | f47ac10b-58cc-4372-a567-0e02b2c3d479
SCODE | ^\d{1,6}$ | 9001
