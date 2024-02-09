<?php

namespace App\Tests\Controller;

use App\DataFixtures\SportFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Sport;
use App\Repository\UserRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
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
        $this->userPasswordHasher = static::getContainer()->get('security.password_hasher');

        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $this->purgeDatabase();

        $fixtureSport = new SportFixtures();
        $fixtureSport->load($this->manager);

        $fixtureUser = new UserFixtures($this->userPasswordHasher);
        $fixtureUser->load($this->manager);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser       = $userRepository->findOneByEmail('user@sportapi.com');

        $this->client->loginUser($testUser);
    }

    private function purgeDatabase(): void
    {
        $purger = new ORMPurger($this->manager);
        $purger->purge();
    }

    public function testShouldListSportsNames(): void
    {
        $client = $this->client;

        $client->request('GET', '/api/sports');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);

    }

    public function testShouldCreateNewSport(): void
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

    public function testShouldNotCreateNewSportWithNullName(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/api/sports/new', [], [], ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('violations', $responseData);
        $this->assertEquals('Le nom du sport est obligatoire', $responseData['violations'][0]['title']);
    }

    public function testShouldNotCreateNewSportIfNameAlreadyExist(): void
    {
        $client = $this->client;

        $client->request(
            'POST',
            '/api/sports/new',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"name": "sport 18"}'
        );

        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('violations', $responseData);
        $this->assertEquals('Ce nom de sport est déjà pris', $responseData['violations'][0]['title']);
    }

    public function testShouldEditSport(): void
    {
        $client          = $this->client;
        $sportRepository = $this->manager->getRepository(Sport::class);
        $sportToUpdate   = $sportRepository->findOneBy([], ['id' => 'DESC']);

        $client->request(
            'PUT',
            '/api/sports/' . $sportToUpdate->getId(), [], [], ['CONTENT_TYPE' => 'application/json'],
            '{"name": "Sport mis à jour"}');

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $updatedSport = $this->manager->getRepository(Sport::class)->find($sportToUpdate->getId());
        $this->assertEquals('Sport mis à jour', $updatedSport->getName());
    }

    public function testShouldNotEditNonExistentSport(): void
    {
        $client = $this->client;

        $client->request(
            'PUT',
            '/api/sports/0', [], [], ['CONTENTTYPE' => 'application/json'],
            '{"name": "Sport mis à jour"}'
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
    public function testShouldRemoveSport(): void
    {
        $client          = $this->client;
        $sportRepository = $this->manager->getRepository(Sport::class);
        $sportToDelete   = $sportRepository->findOneBy([], ['id' => 'DESC']);

        $client->request('DELETE', '/api/sports/' . $sportToDelete->getId());

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
    }
    public function testShouldNotRemoveSportWithWrongId(): void
    {
        $client = $this->client;

        $client->request('DELETE', '/api/sports/0');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}
