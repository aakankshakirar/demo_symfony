<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;
//use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Entity\File;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id')
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('password')
            ->add('dateCreated')
            ->add('dateUpdated')
            ->add('imageFile', VichImageType::class, [
                'allow_file_upload' => true
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
