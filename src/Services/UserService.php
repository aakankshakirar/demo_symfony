<?php

namespace App\Services;

use App\Entity\User;
use App\Form\UserFormType;
use App\Form\UserProfilePictureType;
use App\Repository\UserRepository;
use App\Util\PagerTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

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
     * UserService constructor.
     *
     * @param UserRepository               $userRepository
     * @param EntityManagerInterface       $em
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param Security                     $security
     */
    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordEncoderInterface $passwordEncoder,
        Security $security,
        SluggerInterface $slugger,
        ParameterBagInterface $parameterBag
    )
    {
        $this->userRepository      = $userRepository;
        $this->em                  = $em;
        $this->passwordEncoder     = $passwordEncoder;
        $this->security            = $security;
        $this->slugger             = $slugger;
        $this->userImageUploadPath = $parameterBag->get('userImageUploadPath');
        $this->baseUrl             = $parameterBag->get('base_url');
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
     * @param $page
     * @param $per_page
     *
     * @return array
     */
    public function userList($page, $per_page)
    {
        $result = [];

        $page     = $this->getPage($page);
        $per_page = $this->getLimit($per_page);
        $offset   = $this->getOffset($page, $per_page);

        /** @var User|null $users */
        $users = $this->userRepository->findPaginated($per_page, $offset);

        $total_users = count($this->userRepository->findAll());
        $total_pages = ceil($total_users / $per_page);

        if ($users) {
            foreach ($users as $user) {
                $data     = [
                    'id'         => $user->getId(),
                    'first_name' => $user->getFirstName(),
                    'last_name'  => $user->getLastName(),
                    'email'      => $user->getEmail(),
                    'avatar'     => (!empty($user->getAvatar())) ? $this->baseUrl . 'uploads/users/' . $user->getAvatar() : ''
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
        $data = json_decode((string)$request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (!empty($data['email'])) {
            $this->checkEmailExist($data['email']);
        }

        $user = new User();
        $form = $this->createForm(UserFormType::class, $user);

        $form->submit($data);

        // Check Validations
        foreach ($data as $field => $value) {
            if (!$form->get($field)->isValid()) {
                $errors = implode(", ", $this->getErrorMessages($form));
                throw new HttpException(Response::HTTP_BAD_REQUEST, $errors);
            }
        }

        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * @param $user
     *
     * @return array
     */
    public function editUser($user)
    {
        $data = [
            'id'         => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name'  => $user->getLastName(),
            'email'      => $user->getEmail(),
            'avatar'     => (!empty($user->getAvatar())) ? $user->getAvatar() : '',
            'avatarUrl'  => (!empty($user->getAvatar())) ? $this->baseUrl . 'uploads/users/' . $user->getAvatar() : ''
        ];

        return [
            'status'  => Response::HTTP_OK,
            'message' => "Success",
            'data'    => $data
        ];
    }

    /**
     * @param $request
     * @param $user
     * @return array
     */
    public function updateUser($user, $request)
    {
        $data = json_decode((string)$request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $form = $this->createForm(UserFormType::class, $user);
        $form->submit($data, false);

        $this->em->persist($user);
        $this->em->flush();

        return [
            'status'  => Response::HTTP_OK,
            'message' => "Data updated successfully",
        ];
    }

    /**
     * @param $request
     *
     * @return array
     */
    public function uploadFile($request)
    {
        /* @var User $user */
        $user = new User();
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('imageFile');

        $this->imageValidation($uploadedFile);

        // Move the file to the directory where brochures are stored
        try {
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename     = $this->slugger->slug($originalFilename);
            $newFilename      = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            $uploadedFile->move(
                $this->userImageUploadPath,
                $newFilename
            );

            return [
                'status'   => Response::HTTP_OK,
                'fileName' => $newFilename
            ];
        } catch (FileException $e) {
            throw $e;
        }
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
     * For verify Password
     *
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

    /**
     * For upload image validation
     *
     * @param $uploadedFile
     */
    private function imageValidation($uploadedFile): void
    {
        if (!$uploadedFile) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Please upload an image');
        }
        $client = $uploadedFile->getClientOriginalName();

        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $client)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Special characters are not allowed in filename');
        }
        $ext = pathinfo($client, PATHINFO_EXTENSION);

        if (empty($ext)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'File extension is required');
        }

        if (!in_array($uploadedFile->getMimeType(), [
            'image/jpeg',
            'image/gif',
            'image/png',
        ])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "The MIME type of this file
             is invalid. Allowed type jpeg, gif and png");
        }
    }
}
