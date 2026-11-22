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
            'Achievement',
            'Celebration',
            'Career',
            'Concert',
            'Education',
            'Family',
            'Home',
            'Milestone',
            'Personal',
            'Project',
            'SmallWin',
            'Training',
            'Travel',
        ];

        foreach ($categories as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setColor('default');
            $category->setIsDefault(true);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
