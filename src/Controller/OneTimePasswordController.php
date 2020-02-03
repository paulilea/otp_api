<?php

/*
 * This file is part of the otp-api package.
 *
 * (c) Paul Ilea <paul.ilea90@gmail.com>
 *
 * Copyright lorem...
 */

namespace App\Controller;

use App\Service\OneTimePasswordSessionService;
use App\Service\OneTimePasswordDatabaseService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for One-Time password generation and validation.
 *
 * @Route("/otp")
 */
class OneTimePasswordController extends AbstractController {
    /**
     * @var OneTimePasswordSessionService
     */
    private $sessionService;
    /**
     * @var OneTimePasswordDatabaseService
     */
    private $databaseService;

    /**
     * OneTimePasswordController constructor.
     *
     * @param OneTimePasswordSessionService $sessionService
     * @param OneTimePasswordDatabaseService $databaseService
     */
    public function __construct(
        OneTimePasswordSessionService $sessionService,
        OneTimePasswordDatabaseService $databaseService
    ) {
        $this->sessionService = $sessionService;
        $this->databaseService = $databaseService;
    }

    /**
     * Provides the appropriate service object based on the app configuration.
     *
     * @return OneTimePasswordSessionService|OneTimePasswordDatabaseService
     */
    private function getPasswordService() {
        $useDatabaseStorage = (bool) $this->getParameter('app.use_database_storage');
        if ($useDatabaseStorage) {
            return $this->databaseService;
        }

        return $this->sessionService;
    }

    /**
     * Creates and new One-Time password and returns it to the client.
     *
     * @Route("/{userId}", methods={"GET"}, requirements={"userId"="\d+"}, name="getPassword")
     * @param int $userId
     * @return Response
     */
    public function getPassword(int $userId): Response {
        if ($userId === 0) {
            $response = new Response(
                json_encode(['error' => 'Invalid User Id']),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );

            return $response;
        }

        $passwordService = $this->getPasswordService();
        try {
            $password = $passwordService->create($userId);
        } catch (Exception $exception) {
            $response = new Response(
                json_encode(['error' => 'Password generation failed. Please try again.']),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['content-type' => 'application/json']
            );

            return $response;
        }

        $response = new Response(
            json_encode(['password' => $password]),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );

        return $response;
    }

    /**
     * Checks if the provided One-Time Password is valid.
     *
     * @Route("/{userId}/validate", methods={"POST"}, requirements={"userId"="\d+"}, name="validate")
     * @param Request $request
     * @param int $userId
     * @return Response
     */
    public function validate(Request $request, int $userId): Response {
        if ($userId === 0) {
            $response = new Response(
                json_encode(['error' => 'Invalid User Id']),
                Response::HTTP_BAD_REQUEST,
                ['content-type' => 'application/json']
            );

            return $response;
        }

        $password = $request->get('password') ?? '';
        $passwordService = $this->getPasswordService();
        $isValid = $passwordService->isValid($userId, $password);
        $response = new Response(
            json_encode(['valid' => $isValid]),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );

        return $response;
    }

    /**
     * Handles malformed requests.
     *
     * @Route("/{query}", name="notFound")
     * @return Response
     */
    public function notFound(): Response {
        $response = new Response(
            json_encode(['error' => 'Malformed request']),
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'application/json']
        );

        return $response;
    }
}
