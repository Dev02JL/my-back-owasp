<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $users = [];
        $userEmails = [
            'test@example.com' => 'test123',
            'gus@ta.ve' => 'MotPasse123',
            'ma@el.le' => 'MotPasse123'
        ];

        foreach ($userEmails as $email => $password) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
                $manager->persist($user);
            }
            $users[] = $user;
        }

        // Liste des images dans public/
        $publicDir = __DIR__ . '/../../public/';
        $images = array_values(array_filter(scandir($publicDir), function($file) use ($publicDir) {
            return is_file($publicDir . $file) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
        }));

        // Créer autant de produits qu'il y a d'images
        foreach ($images as $idx => $image) {
            $product = new Product();
            $product->setTitle('Product ' . ($idx + 1));
            $product->setPrice(mt_rand(10, 100));
            $product->setImage($image); // juste le nom du fichier, car il sera servi depuis /public
            $manager->persist($product);

            // Créer 0 à 2 avis pour chaque produit
            $numberOfReviews = mt_rand(0, 2);
            for ($j = 0; $j < $numberOfReviews; $j++) {
                $review = new Review();
                $review->setProduct($product);
                $review->setUser($users[array_rand($users)]); // Pick a random user
                $review->setRating(mt_rand(1, 5));
                $review->setMessage('This is a great review for product ' . ($idx + 1) . '!');
                $manager->persist($review);
            }
        }

        $manager->flush();
    }
} 