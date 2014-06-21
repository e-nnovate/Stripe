// This function is just used to display error messages on the page.
// Assumes there's an element with an ID of "payment-errors" 
function reportError(msg) {
	// Show the error in the form:
	$('#payment-errors').text(msg).addClass('alert alert-error'); // or maybe $('#payment-errors').text(msg).addClass('error'); // requires a css class named error.
	// re-enable the submit button:
	$('#submitBtn').prop('disabled', false);
	return false;
}

// jQuery event handled for the form's submission. i.e. waiting for the document to be ready
$(document).ready(function() {
	
	// Watch for payment-form submission:
	$("#payment-form").submit(function(event) {

		// Flag variable:
		var error = false;
		
		// disable the submit button to prevent repeated clicks
		$('#submitBtn').attr("disabled", "disabled");

		// Get the values:
		var ccNum = $('.card-number').val(), cvcNum = $('.card-cvc').val(), expMonth = $('.card-expiry-month').val(), expYear = $('.card-expiry-year').val();
		
		
		//begin validation. If anything fails validation: error is set to true.
			// Validate the number:
			if (!Stripe.validateCardNumber(ccNum)) {
				error = true;
				reportError('The credit card number appears to be invalid.');
			}
	
			// Validate the CVC:
			if (!Stripe.validateCVC(cvcNum)) {
				error = true;
				reportError('The CVC number appears to be invalid.');
			}
			
			// Validate the expiration:
			if (!Stripe.validateExpiry(expMonth, expYear)) {
				error = true;
				reportError('The expiration date appears to be invalid.');
			}
			
			// Validate any other form elements here
			
		// If no errors occur, make the Ajax request
		if (!error) {
			
			// Get the Stripe token using createToken method of Stripe object.
			Stripe.createToken({
				number: ccNum,
				cvc: cvcNum,
				exp_month: expMonth,
				exp_year: expYear
			}, stripeResponseHandler); //function to handle the method result / Ajax request response.
		}

		// Prevent the payment form from submitting:
		return false;

	}); // Form submission
	
}); // Document ready.

// Function to handle the Stripe response
// Status codes: 200 is good; 400, 401, 402, and 404 mean we screwed up, and 500 codes mean Stripe’s servers messed up
function stripeResponseHandler(status, response) { 
	
	// First check for an error property
	if (response.error) {

		reportError(response.error.message);
		
	} else { // If no errors: Store the token in the form & submit the form.

	  // Get a reference to the form
	  var f = $("#payment-form");

	  // Get the token from the respone. Token contains id, last4, and card type:
	  var token = response['id']; //or use: var token = response.id 
	
	  // Insert the token into a hidden form input in the form so it gets submitted to the server
	  f.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
	
	  // Submit the form:
	  f.get(0).submit();

	}
	
} // End of stripeResponseHandler() function.