<?php

namespace App\Form\Admin;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, ['label' => 'Question', 'attr' => ['rows' => 3]])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => ['QCM' => 'mcq', 'Ouverte' => 'open'],
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Difficulté',
                'choices' => ['Facile (1)' => 1, 'Moyen (2)' => 2, 'Difficile (3)' => 3],
            ])
            ->add('options', TextareaType::class, [
                'label' => 'Options (une par ligne, QCM uniquement)',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => "Option A\nOption B\nOption C"],
            ])
            ->add('correctAnswer', TextType::class, ['label' => 'Bonne réponse']);

        $builder->get('options')->addModelTransformer(new CallbackTransformer(
            fn($array) => is_array($array) ? implode("\n", $array) : '',
            fn($string) => array_values(array_filter(array_map('trim', explode("\n", (string) $string))))
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Question::class]);
    }
}
