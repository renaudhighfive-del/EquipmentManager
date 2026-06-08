<?php

namespace Database\Factories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categorie>
 */
class CategorieFactory extends Factory
{
    protected $model = Categorie::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->unique()->randomElement([
                'Équipement sportif',
                'Équipement informatique',
                'Équipement médical',
                'Équipement militaire',
                'Équipement de bureau',
                'Équipements commerciaux / administratifs',
            ]),
            'description' => $this->faker->sentence(),
        ];
    }
}
