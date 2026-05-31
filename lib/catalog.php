<?php
// Catalogue initial. Sert UNIQUEMENT à pré-remplir la base au premier lancement.
// Ensuite, tout se modifie depuis la page admin (ou directement en base).
//
// Champs :
//   slug        : identifiant court (sert aussi de nom de fichier photo par défaut)
//   category    : catégorie d'affichage
//   name        : nom du produit
//   description : description (PAS de prix, PAS de marque)
//   qty_needed  : quantité encore souhaitée (null = illimité, ex. coups de main)
//   search      : mots-clés pour les liens de recherche Leboncoin / Vinted
//                 (laisser vide '' pour ne PAS afficher de liens, ex. cadeaux "service")

return [
    // 🧴 Soin & Hygiène
    ['slug' => 'lingettes-lavables', 'category' => 'Soin & Hygiène',
     'name' => 'Lingettes lavables douces',
     'description' => "Lingettes lavables en coton, côté doux et de couleur sombre (pour masquer les taches). Réutilisables, pour la toilette et le change.",
     'qty_needed' => 20, 'search' => 'lingettes lavables coton bébé'],

    ['slug' => 'thermometre-frontal', 'category' => 'Soin & Hygiène',
     'name' => 'Thermomètre frontal',
     'description' => "Thermomètre frontal (idéalement sans contact) pour prendre la température de bébé en douceur.",
     'qty_needed' => 1, 'search' => 'thermomètre frontal bébé'],

    // 🍼 Allaitement & Alimentation
    ['slug' => 'biberons-verre', 'category' => 'Allaitement & Alimentation',
     'name' => 'Biberons en verre',
     'description' => "Biberons en verre, plus sains et plus durables que le plastique.",
     'qty_needed' => 4, 'search' => 'biberon verre bébé'],

    ['slug' => 'bavoirs-tissu', 'category' => 'Allaitement & Alimentation',
     'name' => 'Grands bavoirs en tissu',
     'description' => "Grands bavoirs en tissu doux et bien absorbants.",
     'qty_needed' => 7, 'search' => 'bavoir tissu bébé'],

    ['slug' => 'bavoirs-impermeables', 'category' => 'Allaitement & Alimentation',
     'name' => 'Grands bavoirs imperméables',
     'description' => "Grands bavoirs imperméables, faciles à essuyer pour les repas qui débordent.",
     'qty_needed' => 10, 'search' => 'bavoir imperméable bébé'],

    ['slug' => 'bols-silicone', 'category' => 'Allaitement & Alimentation',
     'name' => 'Bols en silicone à ventouse',
     'description' => "Bols pour bébé en silicone avec ventouse anti-renversement.",
     'qty_needed' => 5, 'search' => 'bol silicone ventouse bébé'],

    ['slug' => 'assiettes-silicone', 'category' => 'Allaitement & Alimentation',
     'name' => 'Assiettes en silicone à ventouse',
     'description' => "Assiettes en silicone avec ventouse pour les premiers repas.",
     'qty_needed' => 5, 'search' => 'assiette silicone ventouse bébé'],

    ['slug' => 'pots-verre-conserve', 'category' => 'Allaitement & Alimentation',
     'name' => 'Petits pots en verre',
     'description' => "Petits pots / mini tupperware en verre pour conserver purées et yaourts maison.",
     'qty_needed' => 12, 'search' => 'petit pot verre conserve bébé'],

    // 👕 Vêtements
    ['slug' => 'veste-impermeable-12m', 'category' => 'Vêtements',
     'name' => 'Veste imperméable (12 mois)',
     'description' => "Veste imperméable légère pour l'été, taille 12 mois.",
     'qty_needed' => 1, 'search' => 'veste imperméable bébé 12 mois'],

    ['slug' => 'combinaison-impermeable-18m', 'category' => 'Vêtements',
     'name' => 'Combinaison imperméable (18 mois)',
     'description' => "Combinaison imperméable, taille 18 mois, pour les sorties sous la pluie.",
     'qty_needed' => 1, 'search' => 'combinaison pluie bébé 18 mois'],

    // 🛏️ Meubles & Mobilier
    ['slug' => 'parc-bois-pliant', 'category' => 'Meubles & Mobilier',
     'name' => 'Parc pliant en bois',
     'description' => "Parc pliant / mobile en bois, taille moyenne.",
     'qty_needed' => 1, 'search' => 'parc bébé bois pliant'],

    ['slug' => 'lit-parapluie', 'category' => 'Meubles & Mobilier',
     'name' => 'Lit parapluie',
     'description' => "Lit parapluie pour les déplacements et les siestes nomades.",
     'qty_needed' => 1, 'search' => 'lit parapluie bébé'],

    ['slug' => 'draps-60x120', 'category' => 'Meubles & Mobilier',
     'name' => 'Draps housses 60 x 120',
     'description' => "Draps housses 60 x 120 cm, motifs nature ou geek bienvenus :)",
     'qty_needed' => 3, 'search' => 'drap housse 60x120 bébé'],

    // 🛡️ Sécurité
    ['slug' => 'moustiquaire-lit', 'category' => 'Sécurité',
     'name' => 'Moustiquaire pour lit à barreaux',
     'description' => "Moustiquaire universelle pour lit à barreaux.",
     'qty_needed' => 1, 'search' => 'moustiquaire lit bébé barreaux'],

    // 🧸 Jouets
    ['slug' => 'jeux-bain', 'category' => 'Jouets',
     'name' => 'Jeux pour le bain',
     'description' => "Petits jouets rigolos pour barboter dans le bain.",
     'qty_needed' => 7, 'search' => 'jouet bain bébé'],

    ['slug' => 'arbre-marionnettes-lpo', 'category' => 'Jouets',
     'name' => 'Marionnettes à doigts (oiseaux LPO)',
     'description' => "Marionnettes à doigts façon oiseaux (type LPO) pour raconter de jolies histoires.",
     'qty_needed' => 1, 'search' => 'marionnette doigt oiseau LPO'],

    ['slug' => 'jouets-poussette', 'category' => 'Jouets',
     'name' => 'Jouets pour poussette',
     'description' => "Petits jouets à suspendre à la poussette pour éveiller bébé en balade.",
     'qty_needed' => 3, 'search' => 'jouet poussette bébé'],

    ['slug' => 'livres-bebe', 'category' => 'Jouets',
     'name' => 'Livres pour bébé',
     'description' => "Livres pour bébé (tissu, cartonnés, à toucher…), thèmes nature et animaux appréciés.",
     'qty_needed' => 5, 'search' => 'livre bébé éveil tissu'],

    // 🚲 Voyage & Transport
    ['slug' => 'retroviseur-voiture', 'category' => 'Voyage & Transport',
     'name' => 'Rétroviseur pour la voiture',
     'description' => "Rétroviseur pour surveiller bébé installé dos à la route en voiture.",
     'qty_needed' => 1, 'search' => 'rétroviseur bébé voiture dos route'],

    ['slug' => 'siege-velo', 'category' => 'Voyage & Transport',
     'name' => 'Siège bébé pour vélo',
     'description' => "Siège vélo pour emmener bébé en balade.",
     'qty_needed' => 1, 'search' => 'siège vélo bébé'],

    ['slug' => 'remorque-velo', 'category' => 'Voyage & Transport',
     'name' => 'Remorque vélo pour bébé',
     'description' => "Remorque vélo pour les sorties en famille.",
     'qty_needed' => 1, 'search' => 'remorque vélo bébé'],

    // 💛 Autres (coups de main, ne s'achètent pas)
    ['slug' => 'massage-anna', 'category' => 'Autres (coups de cœur)',
     'name' => 'Un massage pour Anna',
     'description' => "Un bon pour un massage ou un moment de détente offert à Anna, la future maman 💛",
     'qty_needed' => 1, 'search' => ''],

    ['slug' => 'bons-petits-plats', 'category' => 'Autres (coups de cœur)',
     'name' => 'De bons petits plats',
     'description' => "Cuisiner et déposer de bons petits plats pour nous aider les premières semaines. Plusieurs personnes peuvent participer !",
     'qty_needed' => null, 'search' => ''],

    ['slug' => 'coups-de-main', 'category' => 'Autres (coups de cœur)',
     'name' => 'Des coups de main',
     'description' => "Offrir du temps : ménage, courses, présence, garde… Tout coup de main sera précieux. Plusieurs personnes peuvent participer !",
     'qty_needed' => null, 'search' => ''],
];
