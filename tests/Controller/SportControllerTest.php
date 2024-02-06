<?php

namespace App\Tests\Controller;

use App\Entity\Sport;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SportControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Sport::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $client = $this->client;

        $newSport = new Sport();
        $newSport->setName('Nouveau sport');

        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($newSport);
        $entityManager->flush();

        $client->request('GET', '/api/sports');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
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
