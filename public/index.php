<?php
// php complaining about this
date_default_timezone_set('UTC');

require '../vendor/autoload.php';
require '../app/Models/Courriel.php';

$config = require '../app/config.php';

$app = new \Slim\App($config);

\Stripe\Stripe::setApiKey($config['settings']['stripe']['secret_key']);

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($config['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();


$app->get('/', function () {
    echo 'Bonjour! :) <br/> <br/>';
});

$app->group('/v1', function() use ($app) {

    $app->post('/payment/new', function ($request, $response) {

        $token     = $_POST['stripeToken'];
        $amount    = $_POST['amount'];
        $firstName = $_POST['firstName'];
        $lastName  = $_POST['lastName'];
        $email     = $_POST['email'];
        $category  = '(unspecified)';
      
        if (!empty($app->request->params('category'))) {
            switch ($app->request->params('category')) {  
              case 'inscription2018-2019' :
                  $category = $app->request->params('category');
            };
          
        }

        // Charge the user's card
        $charge = \Stripe\Charge::create(array(
            'amount' => $amount,
            'currency' => 'cad',
            'description' => 'Paiement d’inscription 2018-2019 pour ' . $firstName . ' ' . $lastName,
            'source' => $token,
            'metadata' => array(
                'email'     => $email,
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'category'  => $category
            )
        ));

        $decimalAmount = substr_replace($amount, ',', -2, 0);

      return $response->write("<h1>Paiement de $decimalAmount $</h1>");
    });
});

$app->run();
