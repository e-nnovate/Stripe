Stripe API implementation
======

Remote repo for development of Stripe API checkout.

Overview:
Stripe keys are in config.php, for easy changing from testing to live.
In checkout.php, the form is loaded & filled out. No name attributes are used for the payment fields to keep that information off your server.
Ajax handles the form, interrupting it before it reaches the php script, and passing the information to stripe.
Stripe returns a token, and ajax stores it. Any errors at this point are displayed on the web page.
If no errors, then ajax lets the form submission go through to your server/the php script.
the php script then uses the token to request that the payment be processed.
