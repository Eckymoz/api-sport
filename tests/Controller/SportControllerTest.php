<?php

namespace App\Tests\Controller;

use App\DataFixtures\SportFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Sport;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class SportControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private UserPasswordHasherInterface $userPasswordHasher;

    protected function setUp(): void
    {
        $this->client  = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();

        $fixtureSport = new SportFixtures();
        $fixtureSport->load($this->manager);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser       = $userRepository->findOneByEmail('user@bookapi.com');

        $this->client->loginUser($testUser);
    }

    public function testIndex(): void
    {
        $client = $this->client;

        $client->request('GET', '/api/sports');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
    }

    public function testNew(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/api/sports/new', [], [], ['CONTENT_TYPE' => 'application/json'],
            '{"name": "Nouveau Sport"}'
        );

        $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('error_message', $responseData);
    }


    public function testEdit(): void
    {
        $client = $this->client;

        $sportToUpdate = new Sport();
        $sportToUpdate->setName('Sport à mettre à jour');

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($sportToUpdate);
        $entityManager->flush();

        $client->request(
            'PUT',
            '/api/sports/' . $sportToUpdate->getId(), [], [], ['CONTENT_TYPE' => 'application/json'],
            '{"name": "Sport mis à jour"}');

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $updatedSport = $entityManager->getRepository(Sport::class)->find($sportToUpdate->getId());
        $this->assertEquals('Sport mis à jour', $updatedSport->getName());
    }

    public function testRemove(): void
    {
        $client = $this->client;

        $sportToDelete = new Sport();
        $sportToDelete->setName('Sport à supprimer');

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($sportToDelete);
        $entityManager->flush();

        $client->request('DELETE', '/api/sports/' . $sportToDelete->getId());

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
    }
}
