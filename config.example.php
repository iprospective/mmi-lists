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

    // Emails (facultatif) : se règlent aussi depuis l'administration.
    // Laisser vide pour désactiver l'envoi d'emails.
    'email_from' => '', // adresse « De » des emails (ex. liste@mondomaine.fr)
    'email_to'   => '', // destinataire des notifications de réservation

    // Charte graphique (facultatif) : se règle aussi depuis l'administration.
    // Couleurs au format hexadécimal (#rgb ou #rrggbb).
    'theme_bg'     => '#fbf7f2', // couleur de fond
    'theme_heart'  => '#6fae8e', // couleur des cœurs et accents « offert »
    'theme_button' => '#e9a17c', // couleur des boutons
];
