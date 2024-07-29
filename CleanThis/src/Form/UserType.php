<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'E-mail',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
                'label_attr' => ['style' => 'margin-bottom: 3px;margin-top: 5px;']
            ])
            ->add('roles', ChoiceType::class, [
                'choices'  => [
                    'ROLE_ADMIN' => 'ROLE_ADMIN',
                    'ROLE_SENIOR' => 'ROLE_SENIOR',
                    'ROLE_APPRENTI' => 'ROLE_APPRENTI',
                    // 'ROLE_CLIENT' => 'ROLE_CLIENT',
                    'ROLE_USER' => 'ROLE_USER',
                ],
                'expanded' => false,
                'multiple' => false,
                'label' => 'Roles',
                'attr' => [
                    'class' => 'form-control'
                ],
                'label_attr' => ['style' => 'margin-bottom: 3px;margin-top: 5px;']               
            ])
            // ->add('password', PasswordType::class, [
            //     'label' => 'Entrez votre mot de passe',
            //     'attr' => [
            //         'class' => 'form-control'
            //     ]
            // ])
        
            // ->add('google_id')
            // ->add('avatar', FileType::class, [
            //     'label' => 'Image Object',
            //     'attr' => ['class' => 'form-control', 'id' => 'avatar', 'style' => 'margin-top: 5px; margin-bottom: 5px;'], 
            //     'required' => false,
            //     'data_class' => null,
            // ])
            ->add('lastname', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Nom',
                'label_attr' => ['style' => 'margin-bottom: 3px;margin-top: 5px;']
            ])
            ->add('firstname', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Prénom',
                'label_attr' => ['style' => 'margin-bottom: 3px;margin-top: 5px;']
            ])
            ->add('tel', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Téléphone',
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 10,
                        'exactMessage' => 'Veuillez entrer un numéro de téléphone valide.'
                    ])
                ],
                'label_attr' => ['style' => 'margin-bottom: 3px;margin-top: 5px;']
            ])
            // ->add('created_at')
            // ->add('operations_finalisee')
            ->add('address', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Adresse',
                'label_attr' => ['style' => 'margin-bottom: 3px;margin-top: 5px;']
            ])
            ->get('roles')

            ->addModelTransformer(new CallbackTransformer(
                fn ($rolesAsArray) => count($rolesAsArray) ? $rolesAsArray[0] : null,
                fn ($rolesAsString) => [$rolesAsString]
            ));;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
