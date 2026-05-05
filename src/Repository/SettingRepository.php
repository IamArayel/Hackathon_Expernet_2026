<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    public function getValue(string $name, string $default = ''): string
    {
        $setting = $this->findOneBy(['name' => $name]);
        return $setting?->getValue() ?? $default;
    }

    public function setValue(string $name, ?string $value): void
    {
        $setting = $this->findOneBy(['name' => $name]);
        if (!$setting) {
            $setting = new Setting();
            $setting->setName($name);
        }
        $setting->setValue($value);
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }
}
