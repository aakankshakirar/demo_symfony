<?php

namespace App\Controller\Api;

use App\Services\UserService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller\Api
 * @Rest\Route("api/", name="api_user_")
 */
class UserController extends AbstractFOSRestController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[Route('users', name: 'list')]

    /**
     * User List
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        try {
            if ($request->isMethod('post')) {
                $res = $this->userService->userList($request);

                return $this->json($res);
            }
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid Method'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    #[Route('register', name: 'register')]

    /**
     * User Registration
     * @param Request $request
     *
     * @return Response
     */
    public function register(Request $request): Response
    {
        try {
            if ($request->isMethod('post')) {
                $this->userService->register($request);

                return $this->json([
                    'status' => Response::HTTP_OK,
                    'data'   => 'Registered successfully'
                ]);
            }
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid Method'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    #[Route('edit', name: 'edit')]

    /**
     * Get Single User detail
     * @param Request $request
     *
     * @return Response
     */
    public function editUser(Request $request): Response
    {
        try {
            $response = $this->userService->editUser($request);

            return $this->json([
                'status'  => $response['status'],
                'message' => $response['message'],
                'data'    => $response['data']
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    #[Route('update', name: 'update')]

    /**
     * Update user detail
     * @param Request $request
     *
     * @return Response
     */
    public function updateUser(Request $request): Response
    {
        try {
            $response = $this->userService->updateUser($request);

            return $this->json([
                'status'  => $response['status'],
                'message' => $response['message'],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    #[Route('verify-password', name: 'verify_password')]

    /**
     * Verify password to protect user list view
     * @param Request $request
     *
     * @return Response
     */
    public function verifyPassword(Request $request): Response
    {
        try {
            $status = $this->userService->verifyPassword($request);

            return $this->json($status);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }
}
