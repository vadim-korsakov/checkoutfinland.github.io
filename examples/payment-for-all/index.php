<?php
class Checkout 
{
	private $version		= "0001";
	private $language		= "FI";
	private $country		= "FIN";
	private $currency		= "EUR";
	private $device			= "1";
	private $content		= "1";
	private $type			= "0";
	private $algorithm		= "3";
	private $merchant		= "";
	private $password		= "";
	private $stamp			= 0;
	private $amount			= 0;
	private $reference		= "";
	private $message		= "";
	private $return			= "";
	private $cancel			= "";
	private $reject			= "";
	private $delayed		= "";
	private $delivery_date		= "";
	private $firstname		= "";
	private $familyname		= "";
	private $address		= "";
	private $postcode		= "";
	private $postoffice		= "";
	private $status			= "";
	private $email			= "";
	
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

		$mac = 
strtoupper(md5("{$this->version}+{$this->stamp}+{$this->amount}+{$this->reference}+{$this->message}+{$this->language}+{$this->merchant}+{$this->return}+{$this->cancel}+{$this->reject}+{$this->delayed}+{$this->country}+{$this->currency}+{$this->device}+{$this->content}+{$this->type}+{$this->algorithm}+{$this->delivery_date}+{$this->firstname}+{$this->familyname}+{$this->address}+{$this->postcode}+{$this->postoffice}+{$this->password}"));
		$post['VERSION']		= $this->version;
		$post['STAMP']			= $this->stamp;
		$post['AMOUNT']			= $this->amount;
		$post['REFERENCE']		= $this->reference;
		$post['MESSAGE']		= $this->message;
		$post['LANGUAGE']		= $this->language;
		$post['MERCHANT']		= $this->merchant;
		$post['RETURN']			= $this->return;
		$post['CANCEL']			= $this->cancel;
		$post['REJECT']			= $this->reject;
		$post['DELAYED']		= $this->delayed;
		$post['COUNTRY']		= $this->country;
		$post['CURRENCY']		= $this->currency;
		$post['DEVICE']			= $this->device;
		$post['CONTENT']		= $this->content;
		$post['TYPE']			= $this->type;
		$post['ALGORITHM']		= $this->algorithm;
		$post['DELIVERY_DATE']		= $this->delivery_date;
		$post['FIRSTNAME']		= $this->firstname;
		$post['FAMILYNAME']		= $this->familyname;
		$post['ADDRESS']		= $this->address;
		$post['POSTCODE']		= $this->postcode;
		$post['POSTOFFICE']		= $this->postoffice;
		$post['MAC']			= $mac;

		$post['EMAIL']			= $this->email;
		$post['PHONE']			= $this->phone;

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

$co = new Checkout(375917, "SAIPPUAKAUPPIAS"); // merchantID and securitykey (normally about 80 chars)

// if we are returning from payment
if(isset($_GET['MAC'])) 
{ 
	echo '<h1>Checkout API example</h1>';
	echo '<p><a href="xml2.txt">View sourcecode</a></p>';
	if($co->validateCheckout($_GET))
	{
		echo("<p>Checkout transaction MAC CHECK OK, payment status =  ");
		if($co->isPaid($_GET['STATUS'])) 
		{
			echo("Paid.");
		} 
		else 
		{
			echo("Not paid.");
		}
		echo ("</p>");
	} 
	else 
	{
		echo("<p>Checkout transaction MAC CHECK Failed.</p>");	
	}

	echo "<p><a href='http://demo1.checkout.fi/xml2.php'>Start again</a></p>";
	exit;
}

// Order information
$coData				= array();
$coData["stamp"]		= time(); // unique timestamp
$coData["reference"]		= "12344";
$coData["message"]		= "Huonekalutilaus\nPaljon puita,&lehtiä ja muttereita";
$coData["return"]		= "http://demo1.checkout.fi/xml2.php?test=1";
$coData["delayed"]		= "http://demo1.checkout.fi/xml2.php?test=2";
$coData["amount"]		= "1000"; // price in cents
$coData["delivery_date"]	= date("Ymd");
$coData["firstname"]		= "Tero";
$coData["familyname"]		= "Testaaja";
$coData["address"]		= "Ääkköstie 5b3\nKulmaravintolan yläkerta";
$coData["postcode"]		= "33100";
$coData["postoffice"]		= "Tampere";
$coData["email"]		= "support@checkout.fi";
$coData["phone"]		= "0800 552 010";

// coObject for old method
$coObject = $co->getCheckoutObject($coData);
// change stamp for xml method
$coData['stamp'] = time() + 1;
$response =	$co->getCheckoutXML($coData); // get payment button data
$xml = simplexml_load_string($response);

if($xml === false)
{
	echo 'XML rajapinnan käyttö ei onnistunut. Käytä vanhaa tapaa.';
}
else
{
	// paymentURL link is used if a payer somehow manages to fail paying. You can
	// save it to the webstore and later (if needed) send it by email.
	$link = $xml->paymentURL;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Checkout maksudemo</title>
<style type="text/css">
.C1 {
	width: 180px;
	height: 120px;
	border: 1pt solid #a0a0a0;
	display: block;
	float: left;
	margin: 7px;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	clear: none;
	padding: 0;
}

.C1:hover {
	background-color: #f0f0f0;
	border-color: black;
}

.C1 form {
	width: 180px;
	height: 120px;
}

.C1 form span {
	display: table-cell;
	vertical-align: middle;
	height: 92px;
	width: 180px;
}

.C1 form span input {
	margin-left: auto;
	margin-right: auto;
	display: block;
	border: 1pt solid #f2f2f2;
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	padding: 5px;
	background-color: white;
}

.C1:hover form span input {
	border: 1pt solid black;
}

.C1 div {
	text-align: center;
	font-family: arial;
	font-size: 8pt;
}
</style>
</head>
<body>
	<h1>Checkout API example</h1>
	<a href="xml2.txt">View sourcecode</a>
	<h2>Maksunapit verkkosivulle, suositeltu tapa / XML API, Recommended
		method</h2>

	<?php foreach($xml->payments->payment->banks as $bankX):?>
	<?php foreach($bankX as $bank):?>
	<div class='C1'>
		<form action='<?=$bank['url'];?>' method='post'>
			<?php foreach($bank as $key => $value):?>
			<input type='hidden' name='<?=$key;?>' value='<?=htmlspecialchars($value);?>'>
			<?php endforeach;?>
			<span><input type='image' src='<?=$bank['icon'];?>'> </span>
			<div>
				<?=$bank['name'];?>
			</div>
		</form>
	</div>
	<?php endforeach;?>
	<?php endforeach;?>
	<hr style='clear: both;'>
	<h2>Erillinen maksusivu / vanha tapa / deprecated method</h2>
	<form action='https://payment.checkout.fi/' method='post'>
		<?php foreach($coObject as $field => $value):?>
		<input type='hidden' name='<?=$field;?>' value='<?=htmlspecialchars($value);?>'>
		<?php endforeach;?>
		<input type='submit' value='Siirry maksusivulle'>
	</form>
<hr>
Link: <a href="<?php echo $link;?>">link</a>
</body>
</html>
