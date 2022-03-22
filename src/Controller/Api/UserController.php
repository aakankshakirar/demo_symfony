<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Services\UserService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

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

    /**
     * User List
     *
     * @param int $page
     * @param int $per_page
     *
     * @return Response
     * @Route("users/{page}/{per_page}", name="list", methods={"GET"}, defaults={"page": 1, "per_page": "4"})
     */
    public function index(int $page, int $per_page): Response
    {
        try {
            $response = $this->userService->userList($page, $per_page);

            return $this->json($response);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * User Registration
     *
     * @param Request $request
     *
     * @return Response
     * @Route("register", name="register", methods={"POST"})
     */
    public function register(Request $request): Response
    {
        try {
            $this->userService->register($request);

            return $this->json([
                'status' => Response::HTTP_OK,
                'data'   => 'Registered successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get Single User detail
     *
     * @param User $user
     *
     * @return Response
     * @Route("edit/{id}", name="edit", methods={"GET"})
     * @ParamConverter("user", options={"mapping": {"id" : "id"}})
     */
    public function editUser(User $user): Response
    {
        try {
            $response = $this->userService->editUser($user);

            return $this->json($response);

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
     *
     * @param Request $request
     * @param User    $user
     *
     * @return Response
     * @Route("update/{id}", name="update", methods={"POST"})
     * @ParamConverter("user", options={"mapping": {"id" : "id"}})
     */
    public function updateUser(User $user, Request $request): Response
    {
        try {
            $response = $this->userService->updateUser($user, $request);

            return $this->json($response);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update user detail
     *
     * @param Request $request
     *
     * @return Response
     * @Route("uploadFile", name="uploadFile", methods={"POST"})
     */
    public function uploadFile(Request $request): Response
    {
        try {
            $response = $this->userService->uploadFile($request);

            return $this->json($response);

        } catch (\Exception $e) {
            return $this->json([
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify password to protect user list view
     *
     * @param $request
     *
     * @return Response
     * @Route("verify-password", name="verify-password", methods={"POST"})
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
