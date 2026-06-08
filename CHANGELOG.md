# Journal des modifications

Toutes les évolutions notables de la liste de naissance sont consignées ici.

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/).
Versionnage [SemVer](https://semver.org/lang/fr/) : `MAJEUR.MINEUR.CORRECTIF`.

## [Non publié]

### Ajouté
- **Photo d'accueil sur la page de connexion** : si une photo d'en-tête est définie en
  administration, elle s'affiche à gauche du cadre de connexion, sur deux colonnes de
  hauteur identique (l'image remplit sa colonne). En l'absence de photo, le cadre reste
  centré comme avant ; sur mobile, la photo repasse au-dessus du cadre.
- **Licence libre AGPL v3** : ajout du fichier `LICENSE`, d'un `README.md` décrivant
  le projet et l'installation, et d'une mention en pied de page reliant à la licence
  et au code source (<https://github.com/iprospective/mmi-lists>).
- **Validation de réservation par email (double opt-in, optionnel)** : option activable
  depuis l'administration. Une fois activée, une réservation reste **en attente** tant que
  la personne n'a pas cliqué sur le lien de validation reçu par email ; elle n'apparaît pas
  publiquement et ne décompte pas le cadeau. Après validation, les parents sont notifiés et
  le reçu (avec lien de gestion) est envoyé. L'option nécessite un email expéditeur configuré
  et impose la saisie d'une adresse au moment de réserver.
- **Notifications par email à la réservation** : les parents sont prévenus à chaque
  nouvelle réservation (cadeau, quantité, prénom, email), et la personne qui réserve
  reçoit un reçu de confirmation si elle a indiqué son adresse. Adresses **expéditeur**
  et **destinataire** réglables depuis l'administration ; envoi via `mail()` (sans
  dépendance), désactivé tant que les adresses ne sont pas renseignées.
- **Gestion de ses réservations par lien privé** : le reçu de confirmation contient un
  lien unique (`/mes-reservations`) permettant à la personne de voir et d'annuler ses
  réservations sans mot de passe ni connexion. L'annulation reste limitée à ses propres
  réservations (même adresse email).
- **Charte graphique réglable depuis l'administration** : couleur de fond, couleur
  des cœurs (et accents « déjà offert ») et couleur des boutons, choisies via des
  sélecteurs de couleur. Les valeurs sont validées en hexadécimal côté serveur puis
  injectées en variables CSS ; le cœur du pied de page devient un visuel recolorable.
  Chaque couleur personnalisée peut être réinitialisée à sa valeur par défaut d'un
  clic (le bouton n'apparaît que si la couleur a été modifiée).
- **Photo d'en-tête de la liste** : une image illustrant le thème de la liste,
  téléversée depuis l'administration (avec aperçu et possibilité de la retirer). Sa
  **position** (bandeau en haut, ou flottante à droite / à gauche du texte
  d'introduction) et son **format** (rognée pour remplir le cadre, ou image entière)
  sont réglables ; l'affichage repasse en pleine largeur sur mobile.
- **Agrandissement des photos sur la page d'accueil** : un clic (ou la touche
  Entrée) ouvre la photo en plein écran ; clic n'importe où ou Échap pour fermer.
  Sans dépendance externe (`assets/lightbox.js`) ; seules les vraies photos sont
  cliquables, pas le visuel par défaut.
- **Filtres dans l'administration des articles** : par catégorie, par niveau
  d'utilité (« + » / « ++ ») et par « besoin tôt », combinables et conservés dans
  l'URL (donc maintenus après une modification). La réorganisation est désactivée
  tant qu'un filtre « utilité » ou « besoin tôt » masque une partie des articles.
- **Légende des étiquettes sur la page d'accueil** : une courte note explique aux
  visiteurs ce que signifient « + / ++ » (utilité) et « ⏱ Besoin tôt », affichée
  seulement si au moins un article porte une étiquette.
- **Articles regroupés par catégorie dans l'administration**, avec des flèches
  ▲ / ▼ pour changer l'ordre d'affichage d'un article au sein de sa catégorie.
- **Étiquettes de besoin sur les articles** : niveau « + » (utile) ou « ++ » (très
  utile) et marqueur « ⏱ Besoin tôt », réglables depuis l'administration et
  affichés sous forme de badges sur la page d'accueil.
- **Éditeur de texte enrichi (WYSIWYG)** pour le texte d'introduction **et la
  description des articles** : gras, italique, listes et liens, sans dépendance
  externe (éditeur partagé `assets/wysiwyg.js`). Le HTML saisi est assaini côté
  serveur (liste blanche de balises, attributs et scripts neutralisés) puis
  affiché tel quel sur la page d'accueil.

### Modifié
- Après l'enregistrement (ou le déplacement) d'un article en administration, la
  page se recharge directement sur l'article concerné (ancre `#`) au lieu de
  revenir en haut.

### Corrigé
- **Sauts de ligne et paragraphes dans l'éditeur d'introduction** : la touche
  Entrée crée désormais un vrai paragraphe (au lieu d'un `<div>` supprimé par
  l'assainisseur, qui faisait s'effondrer le texte sur une seule ligne). Les
  `<div>` éventuels sont normalisés en paragraphes et le contenu existant en texte
  brut est enveloppé proprement à l'ouverture.

## [0.3.0] - 2026-06-01

### Modifié
- **Réorganisation en mini-framework** : un point d'entrée unique (front controller)
  avec un routeur, et le code réparti en `controllers/`, `services/` et `templates/`.
- URLs propres et lisibles (`/admin/categories`, `/reserve`…) via réécriture
  `.htaccess`, avec prise en charge d'une installation en sous-répertoire.
- Logique métier isolée dans des services (articles, catégories, réservations,
  paramètres, authentification, base de données) ; les contrôleurs orchestrent et
  les gabarits ne font que l'affichage.
- Chargement automatique des classes (autoloader) ; aucune dépendance externe ajoutée.

### Sécurité
- Accès direct au code applicatif (`app/`, `services/`, `controllers/`, `templates/`)
  bloqué côté serveur.

## [0.2.0] - 2026-06-01

### Ajouté
- **Administration multi-pages** avec barre de navigation par onglets.
- Page **Catégories** : ajouter, renommer, changer l'icône, réordonner et supprimer
  les catégories ; le renommage est répercuté sur les articles concernés et la
  suppression est bloquée tant qu'une catégorie est utilisée.
- Page **Paramètres** : modifier depuis l'interface le titre du site, le texte
  d'introduction, le nom des parents et le mot de passe visiteurs (stockés en base).
- Page **Réservations** : liste complète des réservations (article, personne, email,
  quantité, date) avec édition et suppression.
- Page **Personnes** : réservations regroupées par donateur (par email sinon par nom),
  avec renommage et suppression groupée.

### Modifié
- Les catégories (icône et ordre d'affichage) sont désormais gérées en base de données
  plutôt qu'en dur dans le code.
- La page Articles (`admin.php`) permet de changer la catégorie d'un article.

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
[0.3.0]: #
[0.2.0]: #
[0.1.0]: #
