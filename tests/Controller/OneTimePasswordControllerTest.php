<?php

namespace App\Tests\Controller;

use App\Entity\OneTimePassword;
use App\Service\OneTimePasswordDatabaseService;
use App\Service\OneTimePasswordSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Test class for OneTimePasswordController.
 */
class OneTimePasswordControllerTest extends WebTestCase {

    /**
     * Tests the response status code for the One-Time Password generation endpoint.
     *
     * @dataProvider getPasswordRequestProvider
     * @param string $userId
     * @param int $expectedStatusCode
     */
    public function testGetPassword(string $userId, int $expectedStatusCode): void {
        $client = static::createClient();

        $client->request('GET', '/otp/' . $userId);

        $this->assertEquals($expectedStatusCode, $client->getResponse()->getStatusCode());
    }

    /**
     * Tests the return value for the One-Time Password validation endpoint.
     *
     * @dataProvider validateRequestProvider
     * @param string $userIdCreated
     * @param string $createdPassword
     * @param string $userIdSubmitted
     * @param string $submittedPassword
     * @param int $timeInterval
     * @param bool $expectedValidResponse
     */
    public function testValidate(
        string $userIdCreated, string $createdPassword, string $userIdSubmitted, string $submittedPassword,
        int $timeInterval, bool $expectedValidResponse
    ): void {
        ClockMock::register(__CLASS__);
        ClockMock::register(OneTimePasswordSessionService::class);
        ClockMock::register(OneTimePasswordDatabaseService::class);
        ClockMock::register(OneTimePassword::class);
        ClockMock::withClockMock(TRUE);

        $client = static::createClient();
        $appContainer = $client->getKernel()->getContainer();
        $useDatabaseStorage = (bool) $appContainer->getParameter('app.use_database_storage');
        $passwordSalt = $appContainer->getParameter('app.password_salt');
        $encriptedCreatedPassword = crypt($createdPassword, $passwordSalt);

        // Mock the stored password data.
        if ($useDatabaseStorage) {
            $oneTimePassword = new OneTimePassword($userIdCreated, $encriptedCreatedPassword);
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $appContainer->get('doctrine.orm.entity_manager');
            $entityManager->persist($oneTimePassword);
            $entityManager->flush();
        } else {
            $passwordData = ['password' => $encriptedCreatedPassword, 'createdAt' => time()];
            $session = $client->getContainer()->get('session');
            $session->set('otp-' . $userIdCreated, serialize($passwordData));
            $cookie = new Cookie($session->getName(), $session->getId());
            $client->getCookieJar()->set($cookie);
        }

        // Wait for the required test time.
        sleep($timeInterval);

        // Check the validity of the submitted password using the validate endpoint.
        $client->request('POST', '/otp/' . $userIdSubmitted . '/validate', ['password' => $submittedPassword]);
        $response = json_decode($client->getResponse()->getContent(), TRUE);

        $this->assertEquals($expectedValidResponse, $response['valid']);

        ClockMock::withClockMock(FALSE);
    }

    /**
     * @dataProvider getPasswordAndValidateRequestProvider
     * @param int $timeInterval
     * @param int $expectedValidResponse
     */
    public function testGetPasswordAndValidate(int $timeInterval, int $expectedValidResponse): void {
        try {
            $userId = random_int(1, 999);
        } catch (Exception $exception) {
            $userId = 17;
        }

        ClockMock::register(__CLASS__);
        ClockMock::register(OneTimePasswordSessionService::class);
        ClockMock::register(OneTimePasswordDatabaseService::class);
        ClockMock::register(OneTimePassword::class);
        ClockMock::withClockMock(TRUE);

        $client = static::createClient();

        // Fetch a new One-Time Password.
        $client->request('GET', '/otp/' . $userId);

        $getPasswordResponse = json_decode($client->getResponse()->getContent(), TRUE);
        $password = $getPasswordResponse['password'];

        // Wait for the required test time.
        sleep($timeInterval);

        // Check the validity of the generated password after the wait time elapsed.
        $client->request('POST', '/otp/' . $userId . '/validate', ['password' => $password]);
        $validateResponse = json_decode($client->getResponse()->getContent(), TRUE);

        $this->assertEquals($expectedValidResponse, $validateResponse['valid']);

        ClockMock::withClockMock(FALSE);
    }

    public function getPasswordRequestProvider(): array {
        return [
            ['', 404], // Invalid userId. Throws exception in dev-mode
            ['a', 404], // Invalid userId.
            ['12za', 404], // Invalid userId.
            ['0', 400], // Invalid userId.
            ['1', 200], // Valid request.
            ['123', 200], // Valid request.
            ['4551', 200] // Valid request.
        ];
    }

    public function validateRequestProvider(): array {
        return [
            ['10', '010101', '12', '010100', 0, FALSE], // UserId and Password mismatch.
            ['10', '010101', '12', '010101', 0, FALSE], // UserId mismatch.
            ['10', '010101', '10', '010100', 0, FALSE], // Password mismatch.
            ['10', '010101', '10', '010101', 0, TRUE], // Valid combination and time interval.
            ['101', '010101', '101', '010101', 10, TRUE], // Valid combination and time interval.
            ['102', '010101', '102', '010101', 119, TRUE], // Valid combination and time interval.
            ['101', '010101', '101', '010101', 121, FALSE], // Invalid time interval.
            ['101', '010101', '101', '010101', 235, FALSE] // Invalid time interval.
        ];
    }

    public function getPasswordAndValidateRequestProvider(): array {
        return [
            [0, TRUE], // Valid time interval.
            [10, TRUE], // Valid time interval.
            [119, TRUE], // Valid time interval.
            [121, FALSE], // Invalid time interval.
            [173, FALSE], // Invalid time interval.
            [3600, FALSE], // Invalid time interval.
        ];
    }
}
