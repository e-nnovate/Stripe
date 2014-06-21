<?php

require('config.php');

session_start(); // Uses sessions to test for duplicate submissions:

?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Secure Checkout</title>
	<script type="text/javascript" src="https://js.stripe.com/v2/"></script> <!-- Include the stripe library / define the stripe object -->

</head>
<body>
	<script type="text/javascript" src="lib/jquery-1.11.1.js"></script> 


<?php 

echo '<script type="text/javascript">Stripe.setPublishableKey("' . STRIPE_PUBLIC_KEY . '");</script>'; // Set STRIPE_PUBLIC_KEY (from the config file)

// Check for the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	// Store errors in an array
	$errors = array();
	
	// Check for presence of the Stripe token
	if (isset($_POST['stripeToken'])) {
		
		$token = $_POST['stripeToken']; //if $_POST['stripeToken'] is set, assign it to a local var for easy reference
		
		// Make sure this is a new submission by confirming that the submitted token has not already been stored in the session
		// Uses sessions, although cookies are an alternative
		if (isset($_SESSION['token']) && ($_SESSION['token'] == $token)) {
			$errors['token'] = 'You have apparently resubmitted the form. Please do not do that.';
		} else { // New submission.
			$_SESSION['token'] = $token;
		}		
		
	} else {
		$errors['token'] = 'The order cannot be processed. You were not charged. Please check that you have JavaScript enabled and try again.';
	}
	
	
	// ---------------------------------------------
	// Validate any other form fields here
	// If any exist, store them in the $errors array
	// ---------------------------------------------	
	
	
	// Set the purchase amount (in cents) to the user's plan selection on the form
	$amount = $_POST["price"];
	//$amount = 2000;


	// If no errors, process the order
	if (empty($errors)) {
		
		// Wrap evrything in a try...catch block to handle the charge request and catch any errors
		try {
			
			// Include the Stripe library:
			require_once('lib/Stripe.php');
			
			// set thesecret key & remember to change this to live secret key when in production
			// see keys here https://manage.stripe.com/account
			Stripe::setApiKey(STRIPE_PRIVATE_KEY); //stored in config.php
			
			// Create the charge! This code only works for one time charges as written below.
			// For customers with repeating charges we need different code to first create a Stripe customer object
			// and then provide the customer ID as the “card” value to Stripe_Charge::create().
			$charge = Stripe_Charge::create(array(
				"amount" => $amount, // remember amount is in cents
				"currency" => "usd", // always a 3 letter ISO code
				"card" => $token,
				//"description" => $email // we should probably submit a unique customer ID to Stripe here instead of email, but we could put anything we want
				)
			);

			// Check that it was paid using the $charge object to test the success
			// Lots of options available here, like amount, id, livemode (bool), card (last 4 digits), currency (ISO code), paid (bool), description
			if ($charge->paid == true) {
				
				// Store the details in the database
				// Display the invoice to the customer
				// And anything else we need to do now with PHP & MySQL
				// Using the $charge variable and $charge->id and probably $charge->description
				// Remember amount is in cents
				
				$file = 'successlog.txt';
				// Open the file to get existing content
				$current = file_get_contents($file);
				// Append a entry to the file
				$current .= date('Y-m-d H:i:s') . " " . $token . " " . $amount . " " . $_POST['email_address']; // " success \n";
				// Write the contents back to the file
				file_put_contents($file, $current);
				
			} else { // Charge was not paid!	
				echo '<div class="alert alert-error"><h4>Payment System Error!</h4>Your payment could NOT be processed (i.e., you have not been charged) because the payment system rejected the transaction. You can try again or use another card.</div>';
			}
			
		} catch (Stripe_CardError $e) {
		    // Card declined
			$e_json = $e->getJsonBody();
			$err = $e_json['error'];
			$errors['stripe'] = $err['message'];
		} catch (Stripe_ApiConnectionError $e) {
		    // Network problem
		} catch (Stripe_InvalidRequestError $e) {
		    // Error in our programming
		} catch (Stripe_ApiError $e) {
		    // Stripe's servers down
		} catch (Stripe_CardError $e) {
		    // Something else that's not the customer's fault
		}

	} // A user form submission error occurred, handled below.
	
} // Form submission.

?>

	<h1>Subscribe</h1>

	<form action="checkout.php" method="POST" id="payment-form">
		
		<script language="JavaScript">
			var price = 0
			function setPrice(price) { //called by onclick of any radio button to select VPN plan
			      document.forms[0].elements["price"].value = price //set the value of the form's price element (must be in cents)
			      price = price/100
			      price = parseFloat(Math.round(price * 100) / 100).toFixed(2) //force display to the customer of two decimal places 
			      document.getElementById("ShowPrice").innerHTML = "Amount to be charged: $" + price //update the div to show the price
			}
		</script>
		
		<?php // Show PHP errors, if they exist:
		if (isset($errors) && !empty($errors) && is_array($errors)) {
			echo '<div class="alert alert-error"><h4>Error!</h4>The following error(s) occurred:<ul>';
			foreach ($errors as $e) {
				echo "<li>$e</li>";
			}
			echo '</ul></div>';	
		}?>
	
		<div id="payment-errors"></div>
		
		<span class="help-block">
		<p>The only information we collect is your email address and plan. All other information is handled by our payment gateway <a href="http://www.stripe.com/" target="_blank" name="Stripe">Stripe</a>. <br>
		VPN access credentials will be provided on the next screen, after payment.</p>
		You can pay using: Credit cards, debit cards, gift cards, & prepaid debit cards.<br><br>
		List of testing card numbers available here: <a href="https://stripe.com/docs/testing">https://stripe.com/docs/testing</a><br><br></span>
		
		<b>Email Address:</b> <input type="email" name="email_address" /> <!--<b>Coupon:</b> <input type="text" name="cust_coupon" />-->
		<br><br>
		<div style="float: left; margin-left: 10px; ">
			<b>Recurring plans:</b><br>
			<input type="radio" name="cust_plan" value="vpn1w" onClick="setPrice(210)"/>Secure VPN Weekly - $2.10/Every 1 week<br><input type="radio" name="cust_plan" value="vpn1m" onClick="setPrice(650)"/>Secure VPN Monthly - $6.50/Every 1 month<br><input type="radio" name="cust_plan" value="vpn6m" onClick="setPrice(3000)"/>Secure VPN 6 Month - $30.00/Every 6 month<br><input type="radio" name="cust_plan" value="vpn1y" onClick="setPrice(5000)"/>Secure VPN Yearly - $50.00/Every 1 year<br>	
		</div>
		<div style="float: left; margin-left: 10px; width: 330px;  padding: 2px; border-left: 1px solid; ">
			<b>Non-recurring Plans:</b><br>
			<input type="radio" name="cust_plan"  value="nr_vpn1w" onClick="setPrice(410)"/>Secure VPN 1 Week - $4.10<br><input type="radio" name="cust_plan"  value="nr_vpn1m" onClick="setPrice(850)"/>Secure VPN 1 Month - $8.50<br><input type="radio" name="cust_plan"  value="nr_vpn6m" onClick="setPrice(3200)"/>Secure VPN 6 Months - $32.00<br><input type="radio" name="cust_plan"  value="nr_vpn1y" onClick="setPrice(5200)"/>Secure VPN 1 Year - $52.00<br>	
		</div>
		
		<div class="alert alert-info" style="visibility: hidden"><h4>JavaScript Required!</h4>For security purposes, JavaScript is required in order to complete an order.</div>
		
		<div id="credit-card-entry">
			<label>Card Number</label>
			<input type="text" size="20" autocomplete="off" class="card-number input-medium">
			<span class="help-block">(Enter the number without spaces or hyphens)</span>
			<br>
			<label>CVC</label>
			<input type="text" size="4" autocomplete="off" class="card-cvc input-mini">
			<br>
			<label>Expiration (MM/YYYY)</label>
			<input type="text" size="2" class="card-expiry-month input-mini">
			<span> / </span>
			<input type="text" size="4" class="card-expiry-year input-mini">
			<br>
			<input type="hidden" name="price" value="default">
			<br>
			<div id="ShowPrice"></div>
			<br>
			<button type="submit" class="btn" id="submitBtn">Submit Payment</button>
		</div><!-- end credit-card-entry -->

	</form>

	<script src="lib/checkout.js"></script>

</body>
</html>