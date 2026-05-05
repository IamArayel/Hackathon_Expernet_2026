<?php

namespace App\Form\Admin;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('username', TextType::class, ['label' => 'Nom d\'utilisateur'])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => ['Administrateur' => 'ROLE_ADMIN'],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('xp', IntegerType::class, ['label' => 'XP', 'attr' => ['min' => 0]])
            ->add('streak', IntegerType::class, ['label' => 'Série (streak)', 'attr' => ['min' => 0]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
