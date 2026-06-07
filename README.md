# Liste de naissance

Un mini-site de **liste de naissance** privé, simple et sans dépendance : vos proches se
connectent avec un mot de passe partagé, découvrent les affaires dont le bébé a besoin et
réservent un cadeau pour éviter les doublons. **Pas de prix, pas de marque, pas de lien
d'achat** : l'accent est mis sur l'occasion (Leboncoin, Vinted, ressourceries…) et le
fait-maison.

Écrit en **PHP** avec une base **SQLite**, sans framework ni dépendance externe, pour
fonctionner sur un hébergement mutualisé classique.

## Fonctionnalités

- **Accès privé** par mot de passe partagé pour les invités.
- **Catalogue par catégories** : photo, nom, description — sans prix ni boutique.
- **Réservation d'un cadeau** avec prénom + email (l'email reste privé, jamais affiché).
- **Quantités partielles** (ex. « 3 / 20 ») avec barre de progression et bandeau
  « Déjà offert » quand le besoin est couvert ; articles à participation illimitée.
- **Étiquettes** d'utilité (« + » / « ++ ») et « ⏱ Besoin tôt ».
- **Gestion de ses réservations par lien privé** reçu par email (voir / annuler sans
  compte ni mot de passe) ; annulation aussi possible via un jeton en cookie.
- **Notifications par email** aux parents à chaque réservation + reçu de confirmation à
  la personne. Option de **validation par email (double opt-in)** : la réservation reste
  en attente jusqu'au clic sur le lien de validation.
- **Liens de recherche d'occasion** Leboncoin et Vinted par article.
- **Administration complète** : articles (photos, description enrichie, quantité,
  étiquettes, ordre), catégories, réservations, personnes (regroupées par donateur),
  paramètres (titre, intro, parents, mot de passe, emails), charte graphique (couleurs)
  et photo d'en-tête (position / format).
- Aucune dépendance : éditeur enrichi, agrandissement des photos et thème faits maison.

## Prérequis

- **PHP 8.1+** (testé avec PHP 8.5), avec les extensions **PDO SQLite** et **GD**
  (redimensionnement / validation des images).
- Pour l'envoi d'emails : une fonction `mail()` opérationnelle (facultatif).

## Installation

1. **Récupérer le code**

   ```bash
   git clone https://github.com/iprospective/mmi-lists.git
   cd mmi-lists
   ```

2. **Créer la configuration** (contient vos mots de passe, non versionnée) :

   ```bash
   cp config.example.php config.php
   ```

   Puis éditez `config.php` : titre, texte d'accueil, prénoms des parents, **mot de passe
   visiteurs** (`guest_password`) et **mot de passe d'administration** (`admin_password`).
   Les emails et la charte graphique sont facultatifs et réglables ensuite depuis
   l'administration.

3. **Droits d'écriture** : le dossier `data/` (base SQLite) et `img/products/` (photos)
   doivent être accessibles en écriture par le serveur web. La base est créée
   automatiquement au premier chargement.

4. **Déploiement** : pointez le serveur web (Apache) sur la racine du projet. Le
   `.htaccess` fourni active les URLs propres et masque le code applicatif, la base et
   `config.php`. Une installation dans un sous-répertoire est prise en charge.

## Lancer en local

Avec le serveur intégré de PHP :

```bash
php -S 127.0.0.1:8000 index.php
```

Puis ouvrez <http://127.0.0.1:8000>. La page d'accueil demande le mot de passe visiteurs ;
l'administration est accessible sur `/admin` (mot de passe d'administration).

## Organisation du code

Mini-framework maison : un point d'entrée unique (`index.php`) amorce l'application
(`app/bootstrap.php`), qui route la requête vers un contrôleur.

```
app/          Amorçage, routeur, helpers, autoloader
controllers/  Contrôleurs (espace visiteurs + Admin/)
services/     Logique métier (articles, réservations, paramètres, emails, base…)
templates/    Gabarits d'affichage (PHP pur)
assets/       CSS et JavaScript (sans dépendance)
data/         Base SQLite (non versionnée)
img/          Photos d'articles et d'en-tête (non versionnées)
config.php    Configuration locale (non versionnée)
```

## Vie privée

Les emails des donateurs ne sont **jamais affichés publiquement**. La base SQLite, les
photos et `config.php` ne sont pas versionnés et sont bloqués en accès direct par le
serveur.

## Licence

Ce projet est un **logiciel libre** distribué sous licence
[**GNU Affero General Public License v3.0**](https://www.gnu.org/licenses/agpl-3.0.html)
(voir le fichier [`LICENSE`](LICENSE)). Code source :
<https://github.com/iprospective/mmi-lists>.
