<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Highlight;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class HighlightFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $categoryRepo = $manager->getRepository(Category::class);

        $items = [
            ['date' => '2026-01-22', 'title' => '28!', 'category' => 'Celebration'],
            ['date' => '2026-02-18', 'title' => 'Första prototypen i Vue', 'category' => 'Project'],
            ['date' => '2026-03-08', 'title' => 'Vinterresa till Åre', 'category' => 'Travel'],
            ['date' => '2026-05-19', 'title' => 'Halvmaraton i Göteborg', 'category' => 'Training'],
            ['date' => '2026-05-28', 'title' => 'Första kvällen på balkongen', 'category' => 'Home'],
            ['date' => '2026-07-12', 'title' => 'Segling i skärgården', 'category' => 'Travel'],
            ['date' => '2026-08-22', 'title' => 'Sensommarkväll vid havet', 'category' => 'Personal'],
            ['date' => '2026-09-11', 'title' => 'Publicerade Portfolio', 'category' => 'Milestone'],
            ['date' => '2026-10-08', 'title' => 'Italien', 'category' => 'Travel'],
            ['date' => '2026-10-13', 'title' => 'Tog kort för toprepp!', 'category' => 'SmallWin'],
            ['date' => '2026-11-09', 'title' => 'Började LIA', 'category' => 'Education'],
        ];

        foreach ($items as $data) {
            $category = $categoryRepo->findOneBy(['name' => $data['category']]);

            if (!$category instanceof Category) {
                throw new \RuntimeException(sprintf('Category "%s" is missing from the database.', $data['category']));
            }

            $highlight = new Highlight();
            $highlight->setDate(new \DateTimeImmutable($data['date']));
            $highlight->setTitle($data['title']);
            $highlight->setCategory($category);

            $manager->persist($highlight);
        }

        $manager->flush();
    }
}
