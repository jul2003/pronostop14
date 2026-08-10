# 🏉 PronosTOP14

**PronosTOP14** est une application web de pronostics dédiée au championnat de rugby **TOP 14**.

Elle permet à un groupe de joueurs de pronostiquer les résultats des matchs tout au long de la saison, mais également de répondre à des pronostics d'avant-saison comme le champion, le classement final ou les équipes qualifiées pour les phases finales.

L'application prend en charge l'ensemble du cycle d'une saison : préparation, pronostics, saisie des résultats, calcul des points, classements et phases finales.

---

## Fonctionnalités

### 👤 Espace joueur

Les joueurs peuvent :

- rejoindre une saison ;
- compléter leurs pronostics d'avant-saison ;
- pronostiquer chaque journée disponible ;
- pronostiquer :
  - le résultat du match ;
  - le nombre d'essais ;
  - les bonus domicile et extérieur ;
- modifier ou effacer un pronostic tant que le match est ouvert ;
- naviguer directement entre les journées disponibles ;
- visualiser l'état de saisie de leurs pronostics ;
- consulter les résultats et le détail des points obtenus ;
- consulter les classements ;
- consulter les résultats des autres joueurs une fois les pronostics verrouillés ;
- personnaliser leur profil et leur couleur joueur.

L'application gère également les dates limites de saisie et les exceptions éventuelles au niveau d'un match.

---

## 🔮 Pronostics d'avant-saison

La partie avant-saison permet de créer différents types de questions :

- club TOP 14 ;
- club PRO D2 ;
- club participant à la saison ;
- réponse libre.

Exemples :

- Champion TOP 14
- Champion d'automne
- Dernier du TOP 14
- Barragiste
- Vainqueur de la finale PRO D2
- Demi-finalistes TOP 14

Les questions peuvent disposer de leurs propres barèmes et être regroupées pour permettre des corrections collectives.

Des bonus peuvent également être définis lorsqu'un joueur trouve plusieurs réponses liées.

---

## 🤖 Calcul automatique des résultats

Une partie des résultats avant-saison peut être déterminée automatiquement à partir des résultats réels de la saison.

L'application sait notamment détecter :

- une position TOP 14 devenue mathématiquement certaine ;
- les vainqueurs des barrages ;
- le vainqueur de la finale TOP 14 ;
- le vainqueur et le perdant de la finale PRO D2 ;
- le vainqueur de l'Access Match.

Les calculs sont relancés lors de l'enregistrement des résultats.

Lorsqu'une information devient certaine, l'administrateur peut valider les résultats avant-saison détectés automatiquement.

---

## 🏆 Phases finales

PronosTOP14 gère également la génération et l'alimentation des matchs de phases finales.

Le calendrier peut contenir :

1. les journées régulières du TOP 14 ;
2. la finale PRO D2 ;
3. l'Access Match TOP 14 / PRO D2 ;
4. les barrages TOP 14 ;
5. les demi-finales TOP 14 ;
6. la finale TOP 14.

Les équipes peuvent être déterminées automatiquement à partir des résultats précédents.

Par exemple :

- le perdant de la finale PRO D2 participe à l'Access Match ;
- le 13e du TOP 14 participe à l'Access Match ;
- un vainqueur de barrage peut alimenter automatiquement la demi-finale correspondante.

---

## 📊 Barèmes et calcul des points

Les barèmes sont configurables.

Ils permettent notamment d'attribuer des points pour :

- le résultat exact ;
- le nombre exact d'essais ;
- un nombre d'essais proche ;
- un bonus correctement pronostiqué ;
- une journée parfaite.

Différents profils de barème peuvent être utilisés selon le type de journée.

Les pronostics d'avant-saison disposent également de leurs propres profils de scoring.

---

## 🥇 Classements et résultats

Les joueurs peuvent consulter :

- leurs points ;
- les résultats officiels ;
- les pronostics des autres participants après verrouillage ;
- le détail du calcul des points ;
- le classement d'une journée ;
- le classement de la saison.

Le classement saison additionne :

- les points obtenus sur les journées ;
- les points des pronostics d'avant-saison ;
- les éventuels bonus.

En cas d'égalité de points, les joueurs sont départagés dans l'affichage par leur pseudo.

---

## 🛠️ Administration

L'espace administrateur permet de gérer l'ensemble du championnat.

### Saisons

- création d'une saison ;
- activation d'une saison ;
- verrouillage / déverrouillage ;
- configuration du nombre de clubs ;
- sélection des clubs TOP 14 et PRO D2 ;
- sélection des joueurs participants ;
- génération des journées ;
- suppression et régénération du calendrier avant le début des saisies ;
- consultation des saisons précédentes.

Une URL sans saison explicite utilise généralement la saison active.

Les URLs contenant une saison permettent d'administrer ou de consulter une saison historique.

### Journées et matchs

L'administrateur peut :

- gérer les journées ;
- définir les dates ;
- activer ou désactiver la saisie des pronostics ;
- créer les matchs ;
- générer automatiquement certains matchs ;
- définir des dates limites exceptionnelles ;
- saisir et corriger les résultats.

### Résultats

La saisie d'un résultat peut déclencher automatiquement :

- le recalcul des classements ;
- le calcul des points joueurs ;
- la détection de résultats avant-saison ;
- la détermination d'équipes qualifiées ;
- la proposition de création des prochains matchs de phases finales.

### Paramètres globaux

Les paramètres globaux servent de modèle lors de la création des saisons suivantes.

Ils contiennent notamment :

- les questions avant-saison ;
- leurs règles de calcul automatique ;
- les barèmes ;
- les groupes de correction ;
- les bonus.

La configuration d'une saison peut également être réappliquée aux paramètres globaux afin de servir de référence pour la saison suivante.

---

## 👥 Gestion des utilisateurs

L'administration permet notamment de :

- créer et supprimer des utilisateurs ;
- gérer les rôles ;
- rattacher des joueurs à une saison ;
- générer ou imposer un mot de passe temporaire ;
- obliger un utilisateur à changer son mot de passe à sa première connexion.

L'application dispose également d'une procédure classique de mot de passe oublié et de réinitialisation sécurisée.

---

# Stack technique

PronosTOP14 repose principalement sur :

- **Laravel**
- **PHP**
- **MySQL / MariaDB**
- **Blade**
- **Vite**
- **Tailwind CSS 4**
- **Bootstrap 5**
- **Alpine.js**
- **SortableJS**

Les versions exactes utilisées par le projet sont disponibles dans :

```text
composer.json
package.json
```

---

# Installation locale

## 1. Cloner le dépôt

```bash
git clone https://github.com/jul2003/pronostop14.git
cd pronostop14
```

## 2. Installer les dépendances PHP

```bash
composer install
```

## 3. Installer les dépendances JavaScript

```bash
npm install
```

## 4. Créer le fichier d'environnement

```bash
cp .env.example .env
```

Configurer ensuite notamment la connexion à la base de données dans `.env`.

Exemple :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pronostop14
DB_USERNAME=root
DB_PASSWORD=
```

## 5. Générer la clé Laravel

```bash
php artisan key:generate
```

## 6. Créer la base

```bash
php artisan migrate
```

## 7. Compiler les assets

Pour le développement :

```bash
npm run dev
```

Pour un build de production :

```bash
npm run build
```

## 8. Démarrer Laravel

```bash
php artisan serve
```

---

# Commandes utiles

Vider les caches Laravel :

```bash
php artisan optimize:clear
```

Créer les caches de production :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Lancer les migrations :

```bash
php artisan migrate
```

Lancer les tests :

```bash
php artisan test
```

Compiler les assets :

```bash
npm run build
```

---

# Mode maintenance

Mettre l'application en maintenance :

```bash
php artisan down
```

Remettre l'application en ligne :

```bash
php artisan up
```

---

# Déploiement

Exemple de séquence de déploiement sur un serveur de production :

```bash
php artisan down

git pull --ff-only origin main

composer install --no-dev --optimize-autoloader

npm ci
npm run build

php artisan migrate --force

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
```

Si les assets sont compilés avant leur transfert sur le serveur, les commandes NPM peuvent naturellement être adaptées au processus de déploiement utilisé.

---

# Structure du projet

Les principaux éléments métier se trouvent dans :

```text
app/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
├── Services/
└── Support/

resources/
├── css/
├── js/
└── views/

routes/
└── web.php

database/
└── migrations/
```

Une grande partie de la logique métier liée aux calculs, à la génération des journées et aux résultats automatiques est volontairement isolée dans des services Laravel.

---

# Gestion des saisons

PronosTOP14 est conçu pour conserver plusieurs saisons.

Une saison possède sa propre configuration :

- clubs participants ;
- joueurs ;
- journées ;
- matchs ;
- barèmes ;
- pronostics ;
- résultats ;
- configuration avant-saison.

Cela permet de conserver l'historique tout en préparant la saison suivante sans modifier les données des saisons précédentes.

---

# Projet

PronosTOP14 est un projet développé spécifiquement pour organiser un concours privé de pronostics autour du TOP 14.

L'objectif est de proposer une application simple côté joueur tout en automatisant au maximum les tâches administratives :

- calcul des points ;
- classements ;
- phases finales ;
- résultats avant-saison ;
- préparation de la saison suivante.

---

## Licence

Ce dépôt correspond à un projet personnel.

Les dépendances utilisées conservent leurs licences respectives.
