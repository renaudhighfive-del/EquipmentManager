<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Equipement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipement>
 */
class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    /**
     * Marques & modèles réalistes par catégorie.
     */
    private static array $catalogue = [
        'Équipement sportif' => [
            ['marque' => 'Garmin',   'modele' => 'Fenix 7',           'fournisseur' => 'Garmin France'],
            ['marque' => 'Polar',    'modele' => 'Vantage V2',        'fournisseur' => 'Polar Electro'],
            ['marque' => 'Suunto',   'modele' => 'Race',              'fournisseur' => 'Suunto'],
            ['marque' => 'Garmin',   'modele' => 'Edge 1040',         'fournisseur' => 'Garmin France'],
            ['marque' => 'Wahoo',    'modele' => 'ELEMNT Bolt V2',    'fournisseur' => 'Wahoo Fitness'],
        ],
        'Équipement informatique' => [
            ['marque' => 'Dell',     'modele' => 'Latitude 5540',     'fournisseur' => 'Dell'],
            ['marque' => 'HP',       'modele' => 'EliteBook 840 G10', 'fournisseur' => 'HP France'],
            ['marque' => 'Lenovo',   'modele' => 'ThinkPad X1 Carbon','fournisseur' => 'Lenovo'],
            ['marque' => 'Apple',    'modele' => 'MacBook Pro 14',    'fournisseur' => 'Apple Store'],
            ['marque' => 'Logitech', 'modele' => 'MX Keys Mini',      'fournisseur' => 'Logitech'],
        ],
        'Équipement médical' => [
            ['marque' => 'Welch Allyn', 'modele' => 'Connex VSM 6000','fournisseur' => 'Welch Allyn'],
            ['marque' => 'Philips',     'modele' => 'IntelliVue MX40','fournisseur' => 'Philips Healthcare'],
            ['marque' => 'Mindray',     'modele' => 'BeneView T5',    'fournisseur' => 'Mindray'],
            ['marque' => 'GE Healthcare','modele' => 'Dash 3000',     'fournisseur' => 'GE Healthcare'],
            ['marque' => 'Omron',       'modele' => 'M7 Intelli IT',  'fournisseur' => 'Omron Healthcare'],
        ],
        'Équipement militaire' => [
            ['marque' => 'Motorola',  'modele' => 'APX 8000',         'fournisseur' => 'Motorola Solutions'],
            ['marque' => 'Getac',     'modele' => 'V110 G7',          'fournisseur' => 'Getac'],
            ['marque' => 'Panasonic', 'modele' => 'Toughbook 33',     'fournisseur' => 'Panasonic'],
            ['marque' => 'Garmin',    'modele' => 'inReach Messenger','fournisseur' => 'Garmin France'],
            ['marque' => 'Trimble',   'modele' => 'TDC600',           'fournisseur' => 'Trimble'],
        ],
        'Équipement de bureau' => [
            ['marque' => 'Brother',  'modele' => 'MFC-L8900CDW',      'fournisseur' => 'Brother France'],
            ['marque' => 'Epson',    'modele' => 'WorkForce Pro WF-C879R','fournisseur' => 'Epson France'],
            ['marque' => 'Cisco',    'modele' => 'IP Phone 8851',     'fournisseur' => 'Cisco'],
            ['marque' => 'Logitech', 'modele' => 'MeetUp',            'fournisseur' => 'Logitech'],
            ['marque' => 'Poly',     'modele' => 'Studio P21',        'fournisseur' => 'Poly'],
        ],
        'Équipements commerciaux / administratifs' => [
            ['marque' => 'Ingenico', 'modele' => 'Link 2500',         'fournisseur' => 'Ingenico Group'],
            ['marque' => 'Zebra',    'modele' => 'TC52',               'fournisseur' => 'Zebra Technologies'],
            ['marque' => 'Honeywell','modele' => 'CT60',               'fournisseur' => 'Honeywell'],
            ['marque' => 'Samsung',  'modele' => 'Galaxy XCover6 Pro','fournisseur' => 'Samsung France'],
            ['marque' => 'Datalogic','modele' => 'Memor 11',          'fournisseur' => 'Datalogic'],
        ],
    ];

    public function definition(): array
    {
        // Récupère une catégorie existante ou en crée une via CategorieFactory
        $categorie = Categorie::inRandomOrder()->first() ?? Categorie::factory()->create();

        $catalogue  = self::$catalogue[$categorie->nom] ?? [];
        $item       = ! empty($catalogue)
                        ? $this->faker->randomElement($catalogue)
                        : ['marque' => $this->faker->company(), 'modele' => $this->faker->word(), 'fournisseur' => $this->faker->company()];

        return [
            'categorie_id'    => $categorie->id,
            'reference'       => 'REF-' . $this->faker->unique()->numberBetween(10000, 99999),
            'numero_serie'    => $this->faker->unique()->bothify('SN-????????'),
            'imei'            => $this->faker->optional(0.4)->numerify('###############'),
            'code_inventaire' => 'INV-' . $this->faker->unique()->numberBetween(1000, 9999),
            'marque'          => $item['marque'],
            'modele'          => $item['modele'],
            'fournisseur'     => $item['fournisseur'],
            'date_acquisition'=> $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'prix_achat'      => $this->faker->randomFloat(2, 50, 3000),
            'garantie_fin'    => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'etat'            => $this->faker->randomElement(['neuf', 'neuf', 'en_service', 'en_service', 'en_service', 'en_panne', 'en_maintenance']),
            'localisation'    => $this->faker->randomElement(['Entrepôt A', 'Entrepôt B', 'Bureaux Paris', 'Site Lyon', 'Stock Central', 'Agence Marseille']),
            'notes'           => $this->faker->optional(0.3)->sentence(),
            'is_archived'     => false,
        ];
    }
}
