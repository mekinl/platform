<html>
<body>

<script src="https://checkout.stripe.com/checkout.js"></script>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>
	var pk, desc, amnt;
	pk='<?=$_GET['pk']?>';
	desc='<?=$_GET['desc']?>';
	amnt='<?=$_GET['amnt']?>';
	return_url='<?=$_GET['return_url']?>' + '&cash_action=<?=$_GET['cash_action']?>' + '&order_id=<?=$_GET['order_id']?>'
	+ '&creation_date=<?=$_GET['creation_date']?>' + '&element_id=<?=$_GET['element_id']?>';
	cancel_url='<?=$_GET['cancel_url']?>' + '&cash_action=<?=$_GET['cash_action']?>' + '&order_id=<?=$_GET['order_id']?>'
	+ '&creation_date=<?=$_GET['creation_date']?>' + '&element_id=<?=$_GET['element_id']?>';
  var handler = StripeCheckout.configure({
    key: pk,
    token: function(token) {
      // Use the token to create the charge with a server-side script.
      // You can access the token ID with `token.id`
      var returnURL = return_url+"&token="+token.id + "$token_email="+token.email;
      window.location.replace(returnURL);
    }
  });

  $('document').ready(function(e) {
    handler.open({
    description: desc,
    amount: amnt
    });
    e.preventDefault();
  });

  // Close Checkout on page navigation
  $(window).on('popstate', function() {
    handler.close();
  });
</script>
</body>
</html>
