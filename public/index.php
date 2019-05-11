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
    echo 'Bonjour! Vous avez bien rejoint l\'API Corsaire-Chaparral :) <br/> <br/>';
});

$app->group('/v1', function() use ($app) {

    $app->post('/payment/new', function ($request, $response) {

        $token     = $_POST['stripeToken'];
        $amount    = $_POST['amount'];
        $firstName = $_POST['firstName'];
        $lastName  = $_POST['lastName'];
        $email     = $_POST['email'];
        $category  = '(unspecified)';
      
        $requestBody = $request->getParsedBody();
      
        if (!empty($requestBody['category'])) {
            switch ($requestBody['category']) {
              case 'inscription2018-2019' :
                  $category = $requestBody['category'];
                  break;
            };
        }

        try {
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
                ),
                'receipt_email' => $email
            ));

            $decimalAmount = substr_replace($amount, ',', -2, 0);

            mail(
                'admin@corsaire-chaparral.org,corsairechaparal@hotmail.com',
                '[API Corsaire-Chaparral] [Stripe] Paiement reçu',
                "Un nouveau paiement a été effectuée sur corsaire-chaparral.org


                Nom : $firstName $lastName
                Catégorie : $category
                Montant : $amount
                "
            );

          return $response->write("<h1>Paiement de $decimalAmount $ réussi avec succès!</h1>");
        } catch (Exception $e) {
            mail(
                'admin@corsaire-chaparral.org',
                '[API Corsaire-Chaparral] [Stripe] ERREUR',
                "Une erreur est survenue lors d’un paiement sur corsaire-chaparral.org

                ERREUR :
                $e

                Nom : $firstName $lastName
                Catégorie : $category
                Montant : $amount
                "
            );
          
          $response->code(500);
          return $response->write("<h1>Le paiement a échoué.</h1>");
        }
    });
});

$app->run();
