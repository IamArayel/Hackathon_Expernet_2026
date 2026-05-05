<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AiSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('api_key', TextType::class, [
                'label' => 'Clé API NVIDIA NIM',
                'required' => false,
                'attr' => ['placeholder' => 'Laisser vide pour utiliser la variable d\'environnement'],
            ])
            ->add('model', ChoiceType::class, [
                'label' => 'Modèle',
                'choices' => [
                    'Gemma 2 2B (rapide)' => 'google/gemma-2-2b-it',
                    'Gemma 2 9B (équilibré)' => 'google/gemma-2-9b-it',
                    'Llama 3.1 8B' => 'meta/llama-3.1-8b-instruct',
                ],
            ])
            ->add('system_prompt', TextareaType::class, [
                'label' => 'Prompt système',
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'placeholder' => 'Laisser vide pour utiliser le prompt par défaut',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
