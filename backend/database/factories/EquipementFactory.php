<?php

namespace Database\Factories;

use App\Models\Equipement;
use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipement>
 */
class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $marques = ['Apple', 'Samsung', 'Dell', 'HP', 'Lenovo', 'Zebra', 'Honeywell', 'Logitech'];
        $modeles = ['Pro X', 'Galaxy Tab', 'Latitude', 'EliteBook', 'ThinkPad', 'TC52', 'ScanPal', 'MX Master'];
        
        return [
            'categorie_id' => Categorie::factory(),
            'reference' => 'REF-' . $this->faker->unique()->numberBetween(10000, 99999),
            'numero_serie' => $this->faker->unique()->bothify('SN-####-????-####'),
            'imei' => $this->faker->optional(0.7)->numerify('###############'),
            'code_inventaire' => 'INV-' . $this->faker->unique()->numberBetween(1000, 9999),
            'marque' => $this->faker->randomElement($marques),
            'modele' => $this->faker->randomElement($modeles),
            'fournisseur' => $this->faker->company(),
            'date_acquisition' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'prix_achat' => $this->faker->randomFloat(2, 50, 2500),
            'garantie_fin' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'etat' => $this->faker->randomElement(['neuf', 'en_service', 'en_panne', 'en_maintenance', 'en_attente_sinistre', 'reforme', 'perdu']),
            'localisation' => $this->faker->randomElement(['Entrepôt A', 'Bureaux Paris', 'Site Lyon', 'Stock Central']),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'is_archived' => false,
        ];
    }
}
