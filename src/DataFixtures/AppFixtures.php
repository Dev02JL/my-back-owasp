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
        // Create a user for tests
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'test123'));
        $manager->persist($user);

        // Create Products
        for ($i = 1; $i <= 20; $i++) {
            $product = new Product();
            $product->setTitle('Product ' . $i);
            $product->setPrice(mt_rand(10, 100));
            $manager->persist($product);

            // Create Reviews for each product
            for ($j = 1; $j <= 5; $j++) {
                $review = new Review();
                $review->setProduct($product);
                $review->setUser($user);
                $review->setRating(mt_rand(1, 5));
                $review->setMessage('This is a great review for product ' . $i . '!');
                $manager->persist($review);
            }
        }

        $manager->flush();
    }
} 