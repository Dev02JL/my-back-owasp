<?php

$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = '1';

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

$factory = new PasswordHasherFactory([
    PasswordAuthenticatedUserInterface::class => ['algorithm' => 'auto']
]);

$hasher = $factory->getPasswordHasher(User::class);

$user = new User();
$user->setEmail('test@example.com');
$user->setRoles(['ROLE_USER']);

$plainPassword = 'test123';
$hashedPassword = $hasher->hash($plainPassword);
$user->setPassword($hashedPassword);

// Créer l'EntityManager
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

// Sauvegarder l'utilisateur
$entityManager->persist($user);
$entityManager->flush();

echo "Utilisateur créé avec succès !\n";
echo "Email : " . $user->getEmail() . "\n";
echo "Mot de passe hashé : " . $hashedPassword . "\n"; 