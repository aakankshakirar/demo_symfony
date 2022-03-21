<?php

namespace App\Services;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Util\PagerTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Security;

/**
 * Class UserService
 * @package App\Services
 */
class UserService extends AbstractFOSRestController
{
    use PagerTrait;

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var Security
     */
    private $security;

    /**
     * User profile image path
     */
    const AVATAR_PATH = "public/uploads/users/";

    /**
     * UserService constructor.
     *
     * @param UserRepository               $userRepository
     * @param EntityManagerInterface       $em
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param Security                     $security
     */
    public function __construct(UserRepository $userRepository, EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->userRepository  = $userRepository;
        $this->em              = $em;
        $this->passwordEncoder = $passwordEncoder;
        $this->security        = $security;
    }

    /**
     * @param $data
     * @param $form
     */
    public function processForm($data, $form)
    {
        $form->submit($data, false);

        foreach ($data as $field => $value) {
            if (!$form->get($field)->isValid()) {
                $errors = implode(", ", $this->getErrorMessages($form));
                throw new HttpException(Response::HTTP_BAD_REQUEST, $errors);
            }
        }
    }

    /**
     * @param Form $form
     *
     * @return array
     */
    public function getErrorMessages(Form $form)
    {
        $errors = [];
        foreach ($form->all() as $child) {
            foreach ($child->getErrors() as $error) {
                $name          = $child->getName();
                $errors[$name] = $error->getMessage();
            }
        }
        return $errors;
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function userList($request)
    {
        $data           = json_decode((string)$request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $data = $data['params'];
        $result = [];

        $page     = $this->getPage($data['page']);
        $per_page = $this->getLimit($data['per_page']);
        $offset   = $this->getOffset($page, $per_page);

        $users = $this->userRepository->findPaginated($per_page, $offset);

        $total_users = count($this->userRepository->findAll());
        $total_pages = ceil($total_users / $per_page);

        if (!empty($users)) {
            foreach ($users as $user) {
                $data     = [
                    'id'         => $user->getId(),
                    'first_name' => $user->getFirstName(),
                    'last_name'  => $user->getLastName(),
                    'email'      => $user->getEmail(),
                    'avatar'     => (!empty($user->getAvatar())) ? self::AVATAR_PATH . $user->getAvatar() : $user->getAvatar()
                ];
                $result[] = $data;
            }
        }
        return [
            "page"        => $page,
            "per_page"    => $per_page,
            "total"       => $total_users,
            "total_pages" => $total_pages,
            "data"        => $result
        ];
    }

    /**
     * @param $request
     */
    public function register($request)
    {
        $data           = json_decode((string)$request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!empty($data['email'])) {
            $this->checkEmailExist($data['email']);
        }

        $user         = new User();
        $uploadedFile = $request->files->get('imageFile');
        $form         = $this->createForm(UserFormType::class, $user);

        $form->submit(
            array_merge($data, [
                'file' => $request->files->all()
            ])
        );

        // Check Validations
        foreach ($data as $field => $value) {
            if (!$form->get($field)->isValid()) {
                $errors = implode(", ", $this->getErrorMessages($form));
                throw new HttpException(Response::HTTP_BAD_REQUEST, $errors);
            }
        }

        $user->setImageFile($uploadedFile);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function editUser($request)
    {
        $data           = json_decode((string)$request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $data = $data['params'];
        // Validate User ID
        if (empty($data['user_id'])) {
            return [
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => "User ID is required",
                'data'    => ""
            ];
        }

        // Check if User is empty
        $user = $this->userRepository->findOneBy(['id' => $data['user_id']]);
        if (empty($user)) {
            return [
                'status'  => Response::HTTP_OK,
                'message' => "User not found",
                'data'    => ""
            ];
        }

        // Return the data when user is found
        $data = [
            'id'         => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'email'      => $user->getEmail(),
            'avatar'     => (!empty($user->getAvatar())) ? self::AVATAR_PATH . $user->getAvatar() : $user->getAvatar()
        ];

        return [
            'status'  => Response::HTTP_OK,
            'message' => "Success",
            'data'    => $data
        ];
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function updateUser($request)
    {
        $data           = json_decode((string)$request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $data = $data['params'];

        // Validate User ID
        if (empty($data['id'])) {
            return [
                'status'  => Response::HTTP_BAD_REQUEST,
                'message' => "User ID is required",
            ];
        }

        // Check if User is empty
        $user = $this->userRepository->findOneBy(['id' => $data['id']]);
        if (empty($user)) {
            return [
                'status'  => Response::HTTP_OK,
                'message' => "User not found",
            ];
        }

        $uploadedFile = $request->files->get('imageFile');
        $form         = $this->createForm(UserFormType::class, $user);
        $form->submit(
            array_merge($data, [
                'file' => $request->files->all()
            ]), false
        );

        $user->setImageFile($uploadedFile);
        $this->em->persist($user);
        $this->em->flush();

        return [
            'status'  => Response::HTTP_OK,
            'message' => "Data updated successfully",
        ];
    }

    /**
     * @param $email
     */
    public function checkEmailExist($email)
    {
        $checkEmailExist = $this->userRepository->findOneBy(['email' => $email]);

        if ($checkEmailExist) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "User with this email Id already registered");
        }
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function verifyPassword($request)
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if (empty($user)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "JWT token not found");
        }

        if (empty($data['password'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "Password is required");
        }

        // Verified password with the logged in user password
        if (!$this->passwordEncoder->isPasswordValid($user, $data['password'])) {
            return [
                'status'  => Response::HTTP_UNAUTHORIZED,
                'message' => "You have entered a wrong password"
            ];
        }
        return [
            'status'  => Response::HTTP_OK,
            'message' => "Success"
        ];
    }
}
