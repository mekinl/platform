<html>
<body>

<script src="https://checkout.stripe.com/checkout.js"></script>
<script src="http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/aes.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>
	var CryptoJSAesJson = {
	    stringify: function (cipherParams) {
		var j = {ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)};
		if (cipherParams.iv) j.iv = cipherParams.iv.toString();
		if (cipherParams.salt) j.s = cipherParams.salt.toString();
		return JSON.stringify(j);
	    },
	    parse: function (jsonStr) {
		var j = JSON.parse(jsonStr);
		var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: CryptoJS.enc.Base64.parse(j.ct)});
		if (j.iv) cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv)
		if (j.s) cipherParams.salt = CryptoJS.enc.Hex.parse(j.s)
		return cipherParams;
	    }
	}	
	
	var pk, desc, amnt, param, decrypt;
	var tokenCheck = false;
	param=atob('<?=$_GET['param']?>');
	decrypt = JSON.parse(CryptoJS.AES.decrypt(param, "cashmusic", {format: CryptoJSAesJson}).toString(CryptoJS.enc.Utf8));
	pk=decrypt.substring(decrypt.indexOf("&pk")+4,decrypt.indexOf("&amnt"));
	desc=decrypt.substring(decrypt.indexOf("desc")+5,decrypt.indexOf("&pk"));
	amnt=decrypt.substring(decrypt.indexOf("&amnt")+6);
	return_url='<?=$_GET['return_url']?>' + '&cash_action=<?=$_GET['cash_action']?>' + '&order_id=<?=$_GET['order_id']?>'
	+ '&creation_date=<?=$_GET['creation_date']?>' + '&element_id=<?=$_GET['element_id']?>';
	cancel_url='<?=$_GET['cancel_url']?>' + '&cash_action=<?=$_GET['cash_action']?>' + '&order_id=<?=$_GET['order_id']?>'
	+ '&creation_date=<?=$_GET['creation_date']?>' + '&element_id=<?=$_GET['element_id']?>';
  var handler = StripeCheckout.configure({
    key: pk,
    token: function(token) {
      // Use the token to create the charge with a server-side script.
      // You can access the token ID with `token.id`
      var returnURL = return_url+"&token="+token.id + "&token_email="+token.email;
      tokenCheck = true;
      window.location.replace(returnURL);
    },closed: function(){
	if (tokenCheck == false) {
		window.location.replace(cancel_url);
	}
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
