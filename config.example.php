<?php
// Copiez ce fichier en "config.php" puis personnalisez les valeurs.
// config.php contient vos mots de passe : il n'est PAS versionné dans git.

return [
    // Titre affiché en haut du site
    'site_title' => 'Notre liste de naissance',

    // Petit texte d'accueil (sous le titre)
    'intro' => "Bienvenue ! Voici les quelques affaires dont notre futur bébé aurait besoin. "
        . "Pas de prix ni de boutique : nous préférons l'occasion (Leboncoin, Vinted, ressourceries…) "
        . "ou le fait-maison. Cliquez sur un cadeau pour le réserver, ainsi personne n'offrira la même chose. Merci du fond du cœur !",

    // Prénoms des parents (affichage uniquement)
    'parents' => 'Anna & Mathieu',

    // Mot de passe partagé pour ENTRER sur le site (à donner à vos proches)
    'guest_password' => 'bebe2026',

    // Mot de passe d'ADMINISTRATION (page admin.php : photos + réservations)
    'admin_password' => 'changez-moi-vite',
];
