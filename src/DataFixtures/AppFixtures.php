<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Counter;
use App\Entity\Drone;
use App\Entity\Image;
use App\Entity\MapSpot;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Entity\Transporter;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr-FR');
        $users = [];

        // Création de l'admin
        $admin = new User();
        $adminDrone = new Drone();

        $admin->setEmail('naerys.404@gmail.com')
        ->setPassword($this->hasher->hashPassword($admin, 'testtest'))
        ->setNickname('Naerys')
        ->setRoles(['ROLE_ADMIN'])
        ->setFirstname('Laura')
        ->setLastname('Admin')
        ->setAddress("40 rue d'Aoste")
        ->setZip('11100')
        ->setCity('Narbonne')
        ;

        $manager->persist($admin);
        
        //Drone (config) de l'admin pour tests
        $adminDrone->setFrame('Sloop V3 - Pirat Frame')
                   ->setMotors('Lumenier JohnnyFPV Cinematic - 1750Kv')
                   ->setFc('Hobbywing F7')
                   ->setEsc('Hobbywing 60A')
                   ->setCam('Cam DJI')
                   ->setReception('Crossfire')
                   ->setLipoCells(6)
                   ->setUser($admin)      
        ;

        $manager->persist($adminDrone);        

        // Drone (config) de l'admin pour tests
        $adminDrone->setFrame('Sloop V3 - Pirat Frame')
                   ->setMotors('Lumenier JohnnyFPV Cinematic - 1750Kv')
                   ->setFc('Hobbywing F7')
                   ->setEsc('Hobbywing 60A')
                   ->setCam('Cam DJI')
                   ->setReception('Crossfire')
                   ->setLipoCells(6)
                   ->setUser($admin)
        ;

        $manager->persist($adminDrone);

        // Création d'un jeu de fausses données pour les utilisateurs
        for ($i = 0; $i < 9; ++$i) {
            $user = new User();

            $user->setEmail($faker->safeEmail())
            ->setPassword($this->hasher->hashPassword($user, 'testtest'))
            ->setNickname($faker->userName())
            ->setFirstname($faker->firstName())
            ->setLastname($faker->lastName())
            ->setAddress($faker->address())
            ->setZip($faker->postcode())
            ->setCity($faker->city())
            ;

            // Pour mettre sur quelques utilisateurs un complément d'adresse
            $rand = random_int(0, 4);
            if ($rand >= 2) {
                $user->setAddressComplement($faker->secondaryAddress());
            }

            $users[] = $user;
            $manager->persist($user);
        }

        // Mise en place des Catégories d'articles de base
        $categories = [];
        $categoriesName = ['Session', 'Crash', 'Build', 'Review', 'Question', 'Inclassable'];

        foreach ($categoriesName as $value) {
            $category = new Category();
            $category->setName($value);
            $categories[] = $category;
            $manager->persist($category);
        }

        $manager->flush($category);

        // Mise en place des statuts de commande
        $deliveryStatusArray = [];
        $deliveryStatusDescription = ['En cours de préparation', 'Remis au transporteur', 'En cours de livraison', 'Livré', 'Commande annulée'];

        foreach ($deliveryStatusDescription as $index => $value) {
            //transform int to string for set to deliveryStatus 
            $index = strval($index);

            $deliveryStatus = new OrderStatus();
            $deliveryStatus->setStatus($index)
                            ->setStatusDescription($value);
            $deliveryStatusArray[] = $deliveryStatus;
            $manager->persist($deliveryStatus);
        }
        $manager->flush($deliveryStatus);

        // Mise en place des Transporteurs disponibles pour le shop
        $transporterColissimo = new Transporter();
        $transporterColissimo->setName('Colissimo')
                             ->setDescription('Livraison en 2 à 3 jours en France Métropolitaine')
                             ->setPrice(5);
        $manager->persist($transporterColissimo);

        $transporterChronopost = new Transporter();
        $transporterChronopost->setName('Chronopost')
                             ->setDescription('Livraison en moins de 24h en France Métropolitaine')
                             ->setPrice(9);

        $manager->persist($transporterChronopost);

        // Mise en place des counters
        $counters = [];
        $countersName = ['Lipo', 'ESC', 'Frame'];

        foreach ($countersName as $value) {
            $counter = new Counter();
            $counter->setName($value);
            $counter->setCount($faker->randomNumber(2, false));
            $counters[] = $counter;
            $manager->persist($counter);
        }

        // Création d'un jeu de fausses données pour les articles
        $articles = [];

        for ($j = 0; $j < 20; ++$j) {
            $article = new Article();

            $randNumber = $faker->randomDigitNotNull();
            $randCategory = $categories[array_rand($categories)];
            $randAuthor = $users[array_rand($users)];

            $article->setTitle($faker->sentence())
                    ->setContent($faker->paragraphs($randNumber, true))
                    ->setCategory($randCategory)
                    ->setAuthor($randAuthor)
            ;

            $articles[] = $article;
            $manager->persist($article);
        }

        // Ajout de commentaires sur les articles
        $comments = [];

        for ($k = 0; $k < 30; ++$k) {
            $comment = new Comment();
            $comment->setArticle($articles[array_rand($articles)])
                    ->setAuthor($users[array_rand($users)])
                    ->setContent($faker->sentences($randNumber, true))
            ;

            $comments[] = $comment;
            $manager->persist($comment);
        }

        // Création d'un article à la Une
        $adminArticle = new Article();
        $adminNewsImage = new Image();
        $adminNewsImage->setSource('DWS.jpg')->setArticle($adminArticle);

        $adminArticle->setAuthor($admin)
                    ->setTitle('Bienvenue sur SO FPV')
                    ->setContent('Le site SO FPV ouvre ses portes !</br>
                    Il présente une partie blog, dans laquelle vous êtes libres de publier vidéos de vol, crashs, questions et divers contenus en rapport avec le FPV.</br>
                    Vous pouvez organiser une session sur la page dédiée, ou simplement vous inscrire à une session existante.</br></br>
                    
                    La boutique présentée est fictive et les produits proposés ne sont pas disponibles à la vente : ce site est à titre démonstratif pour un projet de développement web. Le système de paiement ne validera donc pas la commande.</br></br>
                    
                    Pour le reste, enjoy & fly safe ;)')
                    ->setCategory($categories[1])
                    ->setAdminNews(true)
                    ->addImage($adminNewsImage)
        ;
        $manager->persist($adminArticle);

        // creation d'un jeu de produits pour la boutique
        $products = [];

        $product1 = new Product();
        $product1->setName('Casque DJI FPV V2')
        ->setPriceHT(569.99)
        ->setDescription("
        ● Résolution 1440 x 810 par écran </br>
        ● Enregistrement Vidéos MP4 à 720p/60im/s </br>
        ● Alimentation Batterie externe entre 7,4 et 17,6V (recommandé 4S)  XT60 </br>
        ● Dimensions 184 x 122 x 110 mm (sans antennes) 202 x 126 x 110 mm (avec antennes) </br>
        ● Poids 415 g (bandeau et antennes inclus) </br>
        ● Ecran Deux écrans de 2\" </br>
        ● Fréquence de rafraîchissement de l'écran 120 Hz </br>
        ● Fréquence de communication 5,725 à 5,850 GHz </br>
        ● Puissance de l'émetteur (EIRP) FCC/MIC : </br>
        ● Mode Vue en direct Mode Faible latence (720p/120 ips) Mode Haute qualité (720p/60 ips) </br>
        ● Encodage vidéo MP4, H.264 </br> 
        ● Formats de lecture vidéo compatibles MP4, MOV, MKV (Encodage vidéo : H264 ; Encodage audio : AAC-LC, AAC-HE, AC-3, DTS, MP3) </br>
        ● Température de fonctionnement 0 à 40 °C (32 à 104 °F) </br>
        ● Puissance d'entrée 7,4 à 17,6 V </br>
        ● FOV Réglable de 30° à 54°. Taille d’image réglable de 50 % à 100 % </br>
        ● Écart pupillaire 58 à 70 mm </br>
        ● Batterie Batterie externe 6,6 à 21,75 V ; consommation totale d’énergie de 7 W </br>
        ● Cartes mémoire compatibles Cartes microSD d’une capacité allant jusqu’à 128 Go </br>
        ")
        ->setImage('product-1.jpg')
        ->setStock(10)
        ->setActive(true)
        ;
        $products[] = $product1;
        $manager->persist($product1);

        $product2 = new Product();
        $product2->setName('Case GoPro Session 5')
        ->setPriceHT(9.90)
        ->setDescription('Impression 3D en filament TPU pour case GoPro Session 5. 
        ')
        ->setImage('product-2.jpg')
        ->setStock(10)
        ->setActive(true)
        ;

        $products[] = $product2;
        $manager->persist($product2);

        $product3 = new Product();
        $product3->setName('Support Immortal T pour frame APEX')
        ->setPriceHT(3)
        ->setDescription('Impression 3D en filament TPU de support Immortal T </br>
        ● Frame APEX 
        ')
        ->setImage('product-3.jpg')
        ->setStock(10)
        ->setActive(true)
        ;
        $products[] = $product3;
        $manager->persist($product3);

        $product4 = new Product();
        $product4->setName('T-shirt SO FPV')
        ->setPriceHT(15.90)
        ->setDescription('
        ● T-Shirt floqué du logo SO FPV </br>
        ● 100 % coton.
        ')
        ->setImage('product-4.png')
        ->setStock(10)
        ->setActive(true)
        ;

        $products[] = $product4;
        $manager->persist($product4);

        for ($p = 0; $p < 4; ++$p) {
            $product = new Product();
            $product->setName($faker->words(5, true))
                    ->setPriceHT($faker->randomFloat(2, 5, 30))
                    ->setDescription($faker->paragraph(2))
                    ->setImage('product-default.jpg')
                    ->setStock($faker->numberBetween(0, 50))
                    ->setActive(true)
            ;
            $products[] = $product;
            $manager->persist($product);
        }

        // Ajout de quelques lieux rééls de drone pour y ajouter des sessions
        $spots = [];

        // Sabla (pro)
        $spot1 = new MapSpot();
        $spot1->setName('La Sabla')
              ->setAuthorization('Télépilotes Pro')
              ->setAddress('5 rue Gilbert Affre, 31830 Plaisance-du-Touch')
              ->setLatitude('43.556387')
              ->setLongitude('1.301499')
              ->setAdminMapSpot(false);

        $spot[] = $spot1;
        $manager->persist($spot1);

        // karting (public)
        $spot2 = new MapSpot();
        $spot2->setName('Karting')
        ->setAuthorization('Public')
        ->setAddress('Rue de la Plage, 31150 Fenouillet')
        ->setLatitude('43.670626')
        ->setLongitude('1.384679')
        ->setAdminMapSpot(false);

        $spot[] = $spot2;
        $manager->persist($spot2);

        // lavoir (public)
        $spot3 = new MapSpot();
        $spot3->setName('Lavoir')
        ->setAuthorization('Public')
        ->setAddress('81400 Blaye-les-Mines')
        ->setLatitude('44.039917')
        ->setLongitude('2.142730')
        ->setAdminMapSpot(false);

        $spot[] = $spot3;
        $manager->persist($spot3);

        // DWS vol indoor avec association (public)
        $spot4 = new MapSpot();
        $spot4->setName('DWS Gymnase')
        ->setAuthorization('Public')
        ->setAddress('26 route de Portet, 31270 Villeneuve-Tolosane')
        ->setLatitude('43.522812')
        ->setLongitude('1.355887')
        ->setAdminMapSpot(true);

        $spot[] = $spot4;
        $manager->persist($spot4);
    
        //Château de Bram ( public)
        $spot5 = new MapSpot();
        $spot5->setName("Château de Bram")
        ->setAuthorization("Public")
        ->setAddress("Valgros, 11150 Bram")
        ->setLatitude("43.237722")
        ->setLongitude("2.130921")
        ->setAdminMapSpot(false);

        $spot[] = $spot5;
        $manager->persist($spot5);

        //Parc des quinze Sols ( public)
        $spot6 = new MapSpot();
        $spot6->setName("Parc des Quinze Sols")
        ->setAuthorization("Public")
        ->setAddress("Chemin du Tiers État, 31700 Blagnac")
        ->setLatitude("43.664566")
        ->setLongitude("1.390432")
        ->setAdminMapSpot(false);

        $spot[] = $spot6;
        $manager->persist($spot6);

        // Château de Bram ( public)
        $spot5 = new MapSpot();
        $spot5->setName('Château de Bram')
        ->setAuthorization('Public')
        ->setAddress('Valgros, 11150 Bram')
        ->setLatitude('43.237722')
        ->setLongitude('2.130921')
        ->setAdminMapSpot(false);

        $spot[] = $spot5;
        $manager->persist($spot5);

        // Parc des quinze Sols ( public)
        $spot6 = new MapSpot();
        $spot6->setName('Parc des Quinze Sols')
        ->setAuthorization('Public')
        ->setAddress('Chemin du Tiers État, 31700 Blagnac')
        ->setLatitude('43.664566')
        ->setLongitude('1.390432')
        ->setAdminMapSpot(false);

        $spot[] = $spot6;
        $manager->persist($spot6);

        $manager->flush();

        // Foret de Bouconne ( public)
        $spot7 = new MapSpot();
        $spot7->setName('Forêt de Bouconne')
        ->setAuthorization('Public')
        ->setAddress('Chem. du Ratelier, 31530 Montaigut-sur-Save')
        ->setLatitude('43.635499837933')
        ->setLongitude('1.2286994043916')
        ->setAdminMapSpot(false);

        $spot[] = $spot7;
        $manager->persist($spot7);

        $manager->flush();
    

   }
}
