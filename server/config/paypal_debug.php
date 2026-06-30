<?php
return [
    'url'=>"https://www.sandbox.paypal.com/cgi-bin/webscr",
    'merchant'=>'lingbohust-sgmerchant@gmail.com',
    'currency'=>'SGD',
    'return_url'=>'https://qls2.szmobitech.com/api/completeOrder',
    'cancel_url'=>'https://qls2.szmobitech.com/api/cancelOrder',
    'notify_url'=>'https://qls2.szmobitech.com/api/notifyPayPal'
];