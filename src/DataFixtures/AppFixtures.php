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
        // Create a user for tests (only if it doesn't exist)
        $existingUser = $manager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        if (!$existingUser) {
            $user = new User();
            $user->setEmail('test@example.com');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'test123'));
            $manager->persist($user);
        } else {
            $user = $existingUser;
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

            // Créer quelques avis pour chaque produit
            for ($j = 1; $j <= 5; $j++) {
                $review = new Review();
                $review->setProduct($product);
                $review->setUser($user);
                $review->setRating(mt_rand(1, 5));
                $review->setMessage('This is a great review for product ' . ($idx + 1) . '!');
                $manager->persist($review);
            }
        }

        $manager->flush();
    }
} 