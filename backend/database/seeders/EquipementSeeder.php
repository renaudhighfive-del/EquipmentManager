<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\EquipementImage;
use Illuminate\Database\Seeder;

class EquipementSeeder extends Seeder
{
    /**
     * 5 équipements réalistes par catégorie, cohérents avec le CategorieFactory.
     */
    private array $catalogue = [
        'Équipement sportif' => [
            ['marque' => 'Garmin',   'modele' => 'Fenix 7',            'fournisseur' => 'Garmin France'],
            ['marque' => 'Polar',    'modele' => 'Vantage V2',         'fournisseur' => 'Polar Electro'],
            ['marque' => 'Suunto',   'modele' => 'Race',               'fournisseur' => 'Suunto'],
            ['marque' => 'Garmin',   'modele' => 'Edge 1040',          'fournisseur' => 'Garmin France'],
            ['marque' => 'Wahoo',    'modele' => 'ELEMNT Bolt V2',     'fournisseur' => 'Wahoo Fitness'],
        ],
        'Équipement informatique' => [
            ['marque' => 'Dell',     'modele' => 'Latitude 5540',      'fournisseur' => 'Dell'],
            ['marque' => 'HP',       'modele' => 'EliteBook 840 G10',  'fournisseur' => 'HP France'],
            ['marque' => 'Lenovo',   'modele' => 'ThinkPad X1 Carbon', 'fournisseur' => 'Lenovo'],
            ['marque' => 'Apple',    'modele' => 'MacBook Pro 14',     'fournisseur' => 'Apple Store'],
            ['marque' => 'Logitech', 'modele' => 'MX Keys Mini',       'fournisseur' => 'Logitech'],
        ],
        'Équipement médical' => [
            ['marque' => 'Welch Allyn',  'modele' => 'Connex VSM 6000', 'fournisseur' => 'Welch Allyn'],
            ['marque' => 'Philips',      'modele' => 'IntelliVue MX40', 'fournisseur' => 'Philips Healthcare'],
            ['marque' => 'Mindray',      'modele' => 'BeneView T5',     'fournisseur' => 'Mindray'],
            ['marque' => 'GE Healthcare','modele' => 'Dash 3000',       'fournisseur' => 'GE Healthcare'],
            ['marque' => 'Omron',        'modele' => 'M7 Intelli IT',   'fournisseur' => 'Omron Healthcare'],
        ],
        'Équipement militaire' => [
            ['marque' => 'Motorola',  'modele' => 'APX 8000',          'fournisseur' => 'Motorola Solutions'],
            ['marque' => 'Getac',     'modele' => 'V110 G7',           'fournisseur' => 'Getac'],
            ['marque' => 'Panasonic', 'modele' => 'Toughbook 33',      'fournisseur' => 'Panasonic'],
            ['marque' => 'Garmin',    'modele' => 'inReach Messenger', 'fournisseur' => 'Garmin France'],
            ['marque' => 'Trimble',   'modele' => 'TDC600',            'fournisseur' => 'Trimble'],
        ],
        'Équipement de bureau' => [
            ['marque' => 'Brother',  'modele' => 'MFC-L8900CDW',           'fournisseur' => 'Brother France'],
            ['marque' => 'Epson',    'modele' => 'WorkForce Pro WF-C879R', 'fournisseur' => 'Epson France'],
            ['marque' => 'Cisco',    'modele' => 'IP Phone 8851',          'fournisseur' => 'Cisco'],
            ['marque' => 'Logitech', 'modele' => 'MeetUp',                 'fournisseur' => 'Logitech'],
            ['marque' => 'Poly',     'modele' => 'Studio P21',             'fournisseur' => 'Poly'],
        ],
        'Équipements commerciaux / administratifs' => [
            ['marque' => 'Ingenico',  'modele' => 'Link 2500',           'fournisseur' => 'Ingenico Group'],
            ['marque' => 'Zebra',     'modele' => 'TC52',                'fournisseur' => 'Zebra Technologies'],
            ['marque' => 'Honeywell', 'modele' => 'CT60',                'fournisseur' => 'Honeywell'],
            ['marque' => 'Samsung',   'modele' => 'Galaxy XCover6 Pro',  'fournisseur' => 'Samsung France'],
            ['marque' => 'Datalogic', 'modele' => 'Memor 11',            'fournisseur' => 'Datalogic'],
        ],
    ];

    private array $localisations = [
        'Entrepôt A', 'Entrepôt B', 'Bureaux Paris',
        'Site Lyon', 'Stock Central', 'Agence Marseille',
    ];

    // Pondération : plus d'équipements en_service que dans les autres états
    private array $etats = [
        'neuf', 'neuf',
        'en_service', 'en_service', 'en_service',
        'en_panne', 'en_maintenance',
    ];

    // Mots clés pour les images Picsum par catégorie
    private array $keywordsByCategory = [
        'Équipement sportif' => 'sports,watch,fitness',
        'Équipement informatique' => 'computer,laptop,tech',
        'Équipement médical' => 'medical,hospital,health',
        'Équipement militaire' => 'military,army,tactical',
        'Équipement de bureau' => 'office,desk,printer',
        'Équipements commerciaux / administratifs' => 'business,terminal,work',
    ];

    public function run(): void
    {
        $counter = 80000;

        $categories = Categorie::all()->keyBy('nom');

        foreach ($this->catalogue as $nomCategorie => $items) {
            $categorie = $categories->get($nomCategorie);
            // Crée la catégorie si elle n'existe pas (cas où CategorieSeeder n'a pas encore tourné)
            if (! $categorie) {
                $categorie = Categorie::create([
                    'nom'         => $nomCategorie,
                    'description' => null,
                ]);
            }

            foreach ($items as $item) {
                $equipement = Equipement::create([
                    'categorie_id'    => $categorie->id,
                    'reference'       => "REF-{$counter}",
                    'numero_serie'    => 'SN-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ0123456789'), 0, 8)),
                    'imei'            => null, // non applicable hors smartphones/PDA
                    'code_inventaire' => 'INV-' . str_pad((string) ($counter - 79999), 4, '0', STR_PAD_LEFT),
                    'marque'          => $item['marque'],
                    'modele'          => $item['modele'],
                    'fournisseur'     => $item['fournisseur'],
                    'date_acquisition'=> now()->subDays(rand(30, 730))->format('Y-m-d'),
                    'prix_achat'      => round(rand(50, 2500) + rand(0, 99) / 100, 2),
                    'garantie_fin'    => now()->addYears(rand(1, 3))->format('Y-m-d'),
                    'etat'            => $this->etats[array_rand($this->etats)],
                    'localisation'    => $this->localisations[array_rand($this->localisations)],
                    'notes'           => null,
                    'is_archived'     => false,
                ]);

                // Ajoute 1 à 3 images par équipement
                $keywords = $this->keywordsByCategory[$nomCategorie] ?? 'technology';
                $nbImages = rand(1, 3);
                for ($i = 0; $i < $nbImages; $i++) {
                    // On utilise un seed pour avoir des images cohérentes par équipement
                    $seed = $counter . $i;
                    // On stocke le path comme un chemin dans storage (mais on utilise aussi l'URL Picsum pour le dev)
                    $path = "equipements/{$equipement->id}_{$i}.jpg";
                    EquipementImage::create([
                        'equipement_id' => $equipement->id,
                        'path' => $path,
                    ]);
                }

                $counter++;
            }
        }
    }
}
