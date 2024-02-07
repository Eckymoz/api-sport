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

    public function test_should_list_sports_names(): void
    {
        $client = $this->client;

        $client->request('GET', '/api/sports');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);

    }

    public function test_should_create_new_sport(): void
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

    public function test_should_not_create_new_sport_with_null_name(): void
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

    public function test_should_fail_if_name_already_exist(): void
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

    public function test_should_edit_sport(): void
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

    public function test_should_fail_edit_non_existent_sport(): void
    {
        $client = $this->client;

        $client->request(
            'PUT',
            '/api/sports/0', [], [], ['CONTENT_TYPE' => 'application/json'],
            '{"name": "Sport mis à jour"}'
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
    public function test_should_remove_sport(): void
    {
        $client          = $this->client;
        $sportRepository = $this->manager->getRepository(Sport::class);
        $sportToDelete   = $sportRepository->findOneBy([], ['id' => 'DESC']);

        $client->request('DELETE', '/api/sports/' . $sportToDelete->getId());

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
    }
    public function test_should_not_remove_sport_with_wrong_id(): void
    {
        $client = $this->client;

        $client->request('DELETE', '/api/sports/0');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}
