# Journal des modifications

Toutes les évolutions notables de la liste de naissance sont consignées ici.

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).
Versionnage [SemVer](https://semver.org/lang/fr/) : `MAJEUR.MINEUR.CORRECTIF`.

## [Non publié]

## [0.1.0] - 2026-05-31

### Ajouté
- Première version du site de liste de naissance (PHP + SQLite, sans dépendance).
- Connexion par mot de passe partagé pour les invités.
- Liste des articles regroupés par catégorie : photo, nom, description — **sans prix, sans marque, sans lien d'achat**.
- Catalogue initial de 24 articles issus de `sources/liste matos bébé.ods`
  (uniquement les lignes marquées « x » en colonne « Ok »).
- Réservation d'un cadeau avec prénom + email (email privé, non affiché).
- Réservation en **quantité partielle** (ex. « 3 / 20 ») avec barre de progression et bandeau « Déjà offert » quand le besoin est couvert.
- Articles à participation **illimitée** (bons petits plats, coups de main).
- Affichage du prénom des personnes ayant réservé.
- **Annulation de sa propre réservation** par le visiteur (via un jeton stocké en cookie).
- Liens de **recherche d'occasion** Leboncoin et Vinted par article (jamais de lien d'achat direct).
- Page d'**administration** (`admin.php`, mot de passe dédié) :
  - upload / remplacement de la photo d'un article ;
  - édition du nom, de la description, de la quantité souhaitée et des mots-clés ;
  - ajout et suppression d'articles ;
  - consultation (avec email) et suppression des réservations.
- Détection automatique des photos par nom d'article (`img/products/<slug>.png`).
- 17 photos associées depuis `sources/Images liste naissance/`.
- Protections de base : jetons CSRF, `.htaccess` masquant la base SQLite et `config.php`.

### À faire / idées
- Photos manquantes : lingettes lavables, veste imperméable 12 mois, moustiquaire lit, livres pour bébé.
- Notification par email aux parents à chaque réservation (optionnel).
- Réorganisation de l'ordre des articles depuis l'admin.

[Non publié]: #
[0.1.0]: #
