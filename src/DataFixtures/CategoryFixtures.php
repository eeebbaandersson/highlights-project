<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Travel', 'icon' => 'plane'],
            ['name' => 'Health', 'icon' => 'heart'],
            ['name' => 'Concert', 'icon' => 'music'],
            ['name' => 'Personal', 'icon' => 'user'],
            ['name' => 'Career', 'icon' => 'briefcase'],
            ['name' => 'Education', 'icon' => 'graduation-cap'],
        ];

        foreach ($categories as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setColor($data['color']);
            $category->setIsDefault(true);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
