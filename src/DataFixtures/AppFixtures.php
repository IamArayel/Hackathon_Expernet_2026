<?php

namespace App\DataFixtures;

use App\Entity\Badge;
use App\Entity\Formation;
use App\Entity\Module;
use App\Entity\Question;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadBadges($manager);
        $this->loadFormations($manager);
        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $users = [
            ['email' => 'anthonycda@test.com', 'username' => 'AnthonyCDA', 'password' => 'azerty01', 'xp' => 350, 'level' => 2],
            ['email' => 'emiecda@test.com',    'username' => 'EmieCDA',    'password' => 'azerty2',  'xp' => 120, 'level' => 1],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setEmail($data['email'])
                ->setUsername($data['username'])
                ->setXp($data['xp'])
                ->setLevel($data['level']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $manager->persist($user);
        }
    }

    private function loadBadges(ObjectManager $manager): void
    {
        $badges = [
            ['name' => 'Premier pas',    'description' => 'Compléter votre premier module',    'icon' => '🎯', 'criteria' => ['type' => 'modules_completed', 'value' => 1]],
            ['name' => 'Apprenti',       'description' => 'Atteindre le niveau 2',              'icon' => '📚', 'criteria' => ['type' => 'level',             'value' => 2]],
            ['name' => 'Centurion XP',   'description' => 'Accumuler 100 XP',                  'icon' => '⚡', 'criteria' => ['type' => 'xp',               'value' => 100]],
            ['name' => 'Expert',         'description' => 'Atteindre le niveau 5',              'icon' => '🏆', 'criteria' => ['type' => 'level',             'value' => 5]],
        ];

        foreach ($badges as $data) {
            $badge = new Badge();
            $badge->setName($data['name'])
                ->setDescription($data['description'])
                ->setIcon($data['icon'])
                ->setCriteria($data['criteria']);
            $manager->persist($badge);
        }
    }

    private function loadFormations(ObjectManager $manager): void
    {
        $formations = [
            [
                'title'       => 'Introduction à l\'IA',
                'description' => 'Découvrez les fondamentaux de l\'intelligence artificielle.',
                'category'    => 'IA',
                'difficulty'  => 'beginner',
                'modules'     => [
                    [
                        'title'   => 'Qu\'est-ce que l\'IA ?',
                        'content' => 'L\'IA est la simulation de l\'intelligence humaine par des machines.',
                        'questions' => [
                            [
                                'content'       => 'Que signifie IA ?',
                                'options'       => ['Intelligence Artificielle', 'Interface Avancée', 'Intégration Automatique', 'Information Analytique'],
                                'correctAnswer' => 'Intelligence Artificielle',
                                'type'          => 'mcq',
                                'difficulty'    => 1,
                            ],
                            [
                                'content'       => 'Quel est l\'objectif principal de l\'IA ?',
                                'options'       => ['Remplacer les humains', 'Simuler l\'intelligence humaine', 'Créer des robots', 'Automatiser uniquement les tâches physiques'],
                                'correctAnswer' => 'Simuler l\'intelligence humaine',
                                'type'          => 'mcq',
                                'difficulty'    => 1,
                            ],
                        ],
                    ],
                    [
                        'title'   => 'Machine Learning vs Deep Learning',
                        'content' => 'Le ML apprend à partir de données, le DL utilise des réseaux de neurones profonds.',
                        'questions' => [
                            [
                                'content'       => 'Quelle technologie utilise des réseaux de neurones artificiels ?',
                                'options'       => ['Machine Learning', 'Deep Learning', 'Expert Systems', 'Rule-based AI'],
                                'correctAnswer' => 'Deep Learning',
                                'type'          => 'mcq',
                                'difficulty'    => 2,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title'       => 'Développement Web Symfony',
                'description' => 'Maîtrisez le framework PHP Symfony pour construire des applications web robustes.',
                'category'    => 'Développement',
                'difficulty'  => 'intermediate',
                'modules'     => [
                    [
                        'title'   => 'Architecture MVC',
                        'content' => 'Symfony suit le patron MVC : Modèle, Vue, Contrôleur.',
                        'questions' => [
                            [
                                'content'       => 'Que représente le "C" dans MVC ?',
                                'options'       => ['Composant', 'Contrôleur', 'Configuration', 'Cache'],
                                'correctAnswer' => 'Contrôleur',
                                'type'          => 'mcq',
                                'difficulty'    => 1,
                            ],
                            [
                                'content'       => 'Quel moteur de templates utilise Symfony par défaut ?',
                                'options'       => ['Blade', 'Smarty', 'Twig', 'Mustache'],
                                'correctAnswer' => 'Twig',
                                'type'          => 'mcq',
                                'difficulty'    => 1,
                            ],
                        ],
                    ],
                    [
                        'title'   => 'Doctrine ORM',
                        'content' => 'Doctrine est l\'ORM de Symfony pour interagir avec la base de données via des entités PHP.',
                        'questions' => [
                            [
                                'content'       => 'Que signifie ORM ?',
                                'options'       => ['Object Relational Mapping', 'Open Resource Management', 'Object Request Model', 'Output Rendering Module'],
                                'correctAnswer' => 'Object Relational Mapping',
                                'type'          => 'mcq',
                                'difficulty'    => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($formations as $i => $formationData) {
            $formation = new Formation();
            $formation->setTitle($formationData['title'])
                ->setDescription($formationData['description'])
                ->setCategory($formationData['category'])
                ->setDifficulty($formationData['difficulty']);
            $manager->persist($formation);

            foreach ($formationData['modules'] as $j => $moduleData) {
                $module = new Module();
                $module->setTitle($moduleData['title'])
                    ->setContent($moduleData['content'])
                    ->setPosition($j + 1)
                    ->setFormation($formation);
                $manager->persist($module);

                foreach ($moduleData['questions'] as $questionData) {
                    $question = new Question();
                    $question->setContent($questionData['content'])
                        ->setOptions($questionData['options'])
                        ->setCorrectAnswer($questionData['correctAnswer'])
                        ->setType($questionData['type'])
                        ->setDifficulty($questionData['difficulty'])
                        ->setModule($module);
                    $manager->persist($question);
                }
            }
        }
    }
}
